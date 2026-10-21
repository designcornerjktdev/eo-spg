<?php

namespace App\Domain\Submissions\Services;

use App\Domain\Forms\Services\DynamicFormValidator;
use App\Domain\Forms\Services\FormDefinitionService;
use App\Enums\FieldType;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionRecorder
{
    public function __construct(
        private readonly DynamicFormValidator $validator,
        private readonly FormDefinitionService $forms,
    ) {}

    /**
     * @param  array<string, mixed>  $answers
     */
    public function record(Assignment $assignment, User $actor, array $answers, bool $consentAccepted = false): FormSubmission
    {
        $assignment->loadMissing(['campaign', 'formTemplate.fields', 'user']);

        if ($actor->isSpg() && $assignment->user_id !== $actor->id) {
            abort(403);
        }

        if ($actor->isSpg() && ! $assignment->isFillableNow()) {
            throw ValidationException::withMessages([
                'assignment' => 'Assignment ini belum bisa diisi. Pastikan campaign aktif dan jadwal masih berjalan.',
            ]);
        }

        $template = $assignment->formTemplate;
        $validated = $this->validator->validate($template, $answers, $consentAccepted);

        return DB::transaction(function () use ($assignment, $actor, $template, $validated, $consentAccepted) {
            $submission = FormSubmission::query()->create([
                'campaign_id' => $assignment->campaign_id,
                'assignment_id' => $assignment->id,
                'form_template_id' => $template->id,
                'submitted_by' => $actor->id,
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
            $text = is_array($raw) ? implode('|', $raw) : ($raw === null ? null : (string) $raw);
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
