<?php

namespace App\Domain\Reporting\Services;

use App\Enums\FieldType;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Export columns and masked row values for a set of entries.
 */
class SubmissionTable
{
    public function __construct(private readonly IdentityMasker $masker) {}

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     * @return array<int, string>
     */
    public function headers(User $viewer, Collection $submissions): array
    {
        $fixed = ['id', 'campaign', 'location', 'area', 'submitted_at', 'status', 'qualified'];
        $fieldKeys = [];

        foreach ($submissions as $submission) {
            foreach ($submission->formTemplate->fields as $field) {
                if ($viewer->isClient() && ! $field->visible_to_client) {
                    continue;
                }

                $fieldKeys[$field->key] = $field->label;
            }
        }

        return array_merge($fixed, array_values($fieldKeys));
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    public function row(User $viewer, FormSubmission $submission, array $headers): array
    {
        $answers = $this->masker->maskAnswers($viewer, $submission, $submission->answers ?? [], $submission->formTemplate);
        $fields = $submission->formTemplate->fields->keyBy('label');
        $fieldsByKey = $submission->formTemplate->fields->keyBy('key');

        $base = [
            'id' => (string) $submission->id,
            'campaign' => $submission->campaign?->name,
            'location' => $submission->location?->name,
            'area' => $submission->location?->area,
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'status' => $submission->status->value,
            'qualified' => $submission->qualified === null ? '' : ($submission->qualified ? 'yes' : 'no'),
        ];

        $row = [];

        foreach ($headers as $header) {
            if (array_key_exists($header, $base)) {
                $row[] = $base[$header] ?? '';

                continue;
            }

            $field = $fields->get($header) ?? $fieldsByKey->get($header);
            $value = $field ? ($answers[$field->key] ?? '') : '';
            // Photos are shown as thumbnails by the Excel export, not as text.
            if ($field?->type === FieldType::Image) {
                $value = '';
            }
            $row[] = is_array($value) ? implode('|', $value) : (string) $value;
        }

        return $row;
    }
}
