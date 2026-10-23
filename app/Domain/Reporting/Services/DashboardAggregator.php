<?php

namespace App\Domain\Reporting\Services;

use App\Enums\FieldType;
use App\Enums\SubmissionStatus;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\SubmissionFieldValue;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardAggregator
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summarize(User $viewer, array $filters = []): array
    {
        $submissions = FormSubmission::query()
            ->visibleTo($viewer)
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, $id) => $query->where('form_submissions.campaign_id', $id))
            ->when($filters['location_id'] ?? null, fn (Builder $query, $id) => $query->where('form_submissions.location_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $query, $from) => $query->whereDate('form_submissions.submitted_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, $to) => $query->whereDate('form_submissions.submitted_at', '<=', $to));

        $active = (clone $submissions)->where('form_submissions.status', SubmissionStatus::Submitted);

        $perDay = (clone $active)
            ->selectRaw('date(form_submissions.submitted_at) as day, count(*) as total')
            ->groupByRaw('date(form_submissions.submitted_at)')
            ->orderBy('day')
            ->get();

        $perLocation = (clone $active)
            ->join('campaign_locations', 'campaign_locations.id', '=', 'form_submissions.location_id')
            ->selectRaw('campaign_locations.name as label, count(form_submissions.id) as total')
            ->groupBy('campaign_locations.id', 'campaign_locations.name')
            ->orderBy('campaign_locations.name')
            ->get();

        // Public links that can receive entries right now: active campaign with a published form.
        $links = CampaignLocation::query()
            ->whereHas('campaign', fn (Builder $campaign) => $campaign->visibleTo($viewer)
                ->when($filters['campaign_id'] ?? null, fn (Builder $query, $id) => $query->whereKey($id)))
            ->with('campaign')
            ->get();

        $custom = $this->customFieldMetrics($active);

        return [
            'totals' => [
                'submissions' => (clone $active)->count(),
                'voided' => (clone $submissions)->where('status', SubmissionStatus::Void)->count(),
                'qualified' => (clone $active)->where('qualified', true)->count(),
                'campaigns' => Campaign::query()->visibleTo($viewer)->count(),
            ],
            'links' => [
                'open' => $links->filter(fn (CampaignLocation $location) => $location->campaign->isAcceptingEntries())->count(),
                'total' => $links->count(),
            ],
            'per_day' => $perDay,
            'per_location' => $perLocation,
            'custom_fields' => $custom,
        ];
    }

    /**
     * Per-field stats for fields marked "show on dashboard", over the same filtered entries as the totals.
     *
     * @param  Builder<FormSubmission>  $active
     * @return array<int, array<string, mixed>>
     */
    private function customFieldMetrics(Builder $active): array
    {
        $values = SubmissionFieldValue::query()
            ->whereIn('form_submission_id', (clone $active)->select('form_submissions.id'))
            ->get()
            ->groupBy('field_key');

        if ($values->isEmpty()) {
            return [];
        }

        // Human labels come from the form definitions; the newest form wins when versions differ.
        $labels = FormField::query()
            ->whereIn('key', $values->keys())
            ->orderByDesc('id')
            ->get(['key', 'label'])
            ->unique('key')
            ->pluck('label', 'key');

        return $values->map(function ($rows, $key) use ($labels) {
            $type = $rows->first()->field_type;
            $metric = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'type' => $type->value,
                'responses' => $rows->count(),
            ];

            if ($type === FieldType::Number) {
                $numbers = $rows->pluck('value_number')->filter(fn ($value) => $value !== null);
                $metric['responses'] = $numbers->count();
                $metric['avg'] = $numbers->isEmpty() ? null : round((float) $numbers->avg(), 1);
                $metric['min'] = $numbers->isEmpty() ? null : (float) $numbers->min();
                $metric['max'] = $numbers->isEmpty() ? null : (float) $numbers->max();

                return $metric;
            }

            $options = $type === FieldType::YesNo
                // "No" used to be stored as an empty string, so anything not truthy counts as No.
                ? $rows->map(fn ($row) => in_array((string) $row->value_text, ['1', 'true'], true) ? 'Yes' : 'No')
                : $rows->flatMap(fn ($row) => explode('|', (string) $row->value_text))->filter(fn ($value) => $value !== '');

            $metric['options'] = $options
                ->countBy()
                ->sortDesc()
                ->map(fn ($total, $label) => ['label' => (string) $label, 'total' => $total])
                ->values()
                ->all();

            return $metric;
        })->values()->all();
    }
}
