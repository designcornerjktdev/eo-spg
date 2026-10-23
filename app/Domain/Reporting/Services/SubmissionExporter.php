<?php

namespace App\Domain\Reporting\Services;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionExporter
{
    public function __construct(private readonly IdentityMasker $masker) {}

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     */
    public function download(User $viewer, Collection $submissions, string $filename = 'submissions.csv'): StreamedResponse
    {
        $submissions->loadMissing(['formTemplate.fields', 'location', 'campaign']);

        $headers = $this->headers($viewer, $submissions);

        return response()->streamDownload(function () use ($viewer, $submissions, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($submissions as $submission) {
                fputcsv($handle, $this->row($viewer, $submission, $headers));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     * @return array<int, string>
     */
    private function headers(User $viewer, Collection $submissions): array
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
    private function row(User $viewer, FormSubmission $submission, array $headers): array
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
            $row[] = is_array($value) ? implode('|', $value) : (string) $value;
        }

        return $row;
    }
}
