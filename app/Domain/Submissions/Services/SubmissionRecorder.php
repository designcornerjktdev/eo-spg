<?php

namespace App\Domain\Submissions\Services;

use App\Domain\Forms\Services\DynamicFormValidator;
use App\Domain\Forms\Services\FormDefinitionService;
use App\Enums\FieldType;
use App\Enums\SubmissionStatus;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmissionRecorder
{
    /** Uploaded images are private; staff view them through SubmissionController::file. */
    public const IMAGE_DISK = 'local';

    public function __construct(
        private readonly DynamicFormValidator $validator,
        private readonly FormDefinitionService $forms,
        private readonly Geofence $geofence,
        private readonly ImageOptimizer $images,
    ) {}

    /**
     * Records one public entry for a campaign location, using the campaign's current form.
     *
     * @param  array<string, mixed>  $answers
     * @param  array{latitude?: float|null, longitude?: float|null, accuracy?: float|null}  $position  device location from the browser
     * @param  string|null  $clientToken  random per filled-in form; a repeat (double tap, retry) returns the first entry
     */
    public function record(CampaignLocation $location, array $answers, bool $consentAccepted = false, array $position = [], ?string $clientToken = null): FormSubmission
    {
        if ($existing = $this->alreadyRecorded($clientToken)) {
            return $existing;
        }

        $campaign = $location->campaign;
        $template = $campaign?->currentForm();

        if (! $campaign || ! $template || ! $campaign->isAcceptingEntries()) {
            throw ValidationException::withMessages([
                'form' => 'This form is not accepting entries right now.',
            ]);
        }

        $template->loadMissing('fields');
        $validated = $this->validator->validate($template, $answers, $consentAccepted);
        $geo = $this->geofence->check($location, $position);
        $validated = $this->normalize($template, $validated);
        $stored = $this->storeImages($template, $validated, $campaign->id);
        $validated = [...$validated, ...$stored];

        try {
            return $this->persist($campaign, $location, $template, $validated, $consentAccepted, [...$geo, 'client_token' => $clientToken]);
        } catch (UniqueConstraintViolationException $exception) {
            // The same form arrived twice at once and the other request saved first.
            Storage::disk(self::IMAGE_DISK)->delete(array_values($stored));

            return $this->alreadyRecorded($clientToken) ?? throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk(self::IMAGE_DISK)->delete(array_values($stored));

            throw $exception;
        }
    }

    private function alreadyRecorded(?string $clientToken): ?FormSubmission
    {
        return filled($clientToken) ? FormSubmission::query()->where('client_token', $clientToken)->first() : null;
    }

    /**
     * Records one entry from a spreadsheet import: no geofence, no photos, consent taken on paper,
     * and accepted even when the campaign is closed.
     *
     * @param  array<string, mixed>  $answers
     */
    public function import(CampaignLocation $location, FormTemplate $template, array $answers, CarbonInterface $submittedAt): FormSubmission
    {
        $template->loadMissing('fields');
        $validated = $this->validator->validate($template, $answers, consentAccepted: true, skipImages: true);
        $validated = $this->normalize($template, $validated);

        return $this->persist($location->campaign, $location, $template, $validated, true, [], $submittedAt);
    }

    /**
     * Checks one import row without saving it.
     *
     * @param  array<string, mixed>  $answers
     */
    public function validateImport(FormTemplate $template, array $answers): void
    {
        $template->loadMissing('fields');
        $this->validator->validate($template, $answers, consentAccepted: true, skipImages: true);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $geo
     */
    private function persist($campaign, CampaignLocation $location, FormTemplate $template, array $validated, bool $consentAccepted, array $geo, ?CarbonInterface $submittedAt = null): FormSubmission
    {
        return DB::transaction(function () use ($campaign, $location, $template, $validated, $consentAccepted, $geo, $submittedAt) {
            $submission = FormSubmission::query()->create([
                'campaign_id' => $campaign->id,
                'location_id' => $location->id,
                ...$geo,
                'form_template_id' => $template->id,
                'answers' => $validated,
                'consent_accepted' => $template->requires_consent ? true : $consentAccepted,
                'consent_text_version' => $template->requires_consent ? $template->consent_text : null,
                'qualified' => $this->qualifies($template, $validated),
                'submitted_at' => $submittedAt ?? now(),
                'status' => SubmissionStatus::Submitted,
            ]);

            $this->indexDashboardValues($submission, $template, $validated);
            $this->forms->lock($template);

            return $submission->fresh(['fieldValues']);
        });
    }

    /**
     * Multipart posts (forms with an image) send booleans as "1"/"0"; store them as real booleans.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function normalize(FormTemplate $template, array $answers): array
    {
        foreach ($template->fields as $field) {
            if ($field->type === FieldType::YesNo && isset($answers[$field->key]) && ! is_bool($answers[$field->key])) {
                $answers[$field->key] = filter_var($answers[$field->key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $answers;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, string> field key => stored path
     */
    private function storeImages(FormTemplate $template, array $answers, int $campaignId): array
    {
        $paths = [];

        foreach ($template->fields as $field) {
            $file = $answers[$field->key] ?? null;

            if ($field->type === FieldType::Image && $file instanceof UploadedFile) {
                $paths[$field->key] = $this->images->store($file, 'submissions/'.$campaignId, self::IMAGE_DISK);
            }
        }

        return $paths;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function qualifies(FormTemplate $template, array $answers): ?bool
    {
        if (! $template->qualification_enabled) {
            return null;
        }

        $rule = $template->qualification_rule['type'] ?? 'all_yes_no_true';

        if ($rule !== 'all_yes_no_true') {
            return null;
        }

        foreach ($template->fields as $field) {
            if ($field->type !== FieldType::YesNo) {
                continue;
            }

            if (! filter_var($answers[$field->key] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function indexDashboardValues(FormSubmission $submission, FormTemplate $template, array $answers): void
    {
        foreach ($template->fields as $field) {
            if (! $field->show_on_dashboard || ! $field->type->canShowOnDashboard()) {
                continue;
            }

            $raw = $answers[$field->key] ?? null;
            $text = match (true) {
                is_array($raw) => implode('|', $raw),
                // Store booleans as 1/0: (string) false is "", which reads like "no answer".
                is_bool($raw) => $raw ? '1' : '0',
                $raw === null => null,
                default => (string) $raw,
            };
            $number = $field->type === FieldType::Number && is_numeric($raw) ? $raw : null;

            $submission->fieldValues()->create([
                'field_key' => $field->key,
                'field_type' => $field->type,
                'value_text' => $text,
                'value_number' => $number,
            ]);
        }
    }
}
