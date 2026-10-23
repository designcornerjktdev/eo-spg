<?php

namespace App\Domain\Submissions\Services;

use App\Domain\Forms\Services\DynamicFormValidator;
use App\Domain\Forms\Services\FormDefinitionService;
use App\Enums\FieldType;
use App\Enums\SubmissionStatus;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionRecorder
{
    public function __construct(
        private readonly DynamicFormValidator $validator,
        private readonly FormDefinitionService $forms,
        private readonly Geofence $geofence,
    ) {}

    /**
     * Records one public entry for a campaign location, using the campaign's current form.
     *
     * @param  array<string, mixed>  $answers
     * @param  array{latitude?: float|null, longitude?: float|null, accuracy?: float|null}  $position  device location from the browser
     */
    public function record(CampaignLocation $location, array $answers, bool $consentAccepted = false, array $position = []): FormSubmission
    {
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

        return DB::transaction(function () use ($campaign, $location, $template, $validated, $consentAccepted, $geo) {
            $submission = FormSubmission::query()->create([
                'campaign_id' => $campaign->id,
                'location_id' => $location->id,
                ...$geo,
                'form_template_id' => $template->id,
                'answers' => $validated,
                'consent_accepted' => $template->requires_consent ? true : $consentAccepted,
                'consent_text_version' => $template->requires_consent ? $template->consent_text : null,
                'qualified' => $this->qualifies($template, $validated),
                'submitted_at' => now(),
                'status' => SubmissionStatus::Submitted,
            ]);

            $this->indexDashboardValues($submission, $template, $validated);
            $this->forms->lock($template);

            return $submission->fresh(['fieldValues']);
        });
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
