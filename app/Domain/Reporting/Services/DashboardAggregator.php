<?php

namespace App\Domain\Reporting\Services;

use App\Enums\AssignmentStatus;
use App\Enums\FieldType;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Campaign;
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
            ->when($filters['location_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('assignment', fn (Builder $assignment) => $assignment->where('location_id', $id)))
            ->when(($filters['spg_id'] ?? null) && $viewer->isSuperadmin(), fn (Builder $query) => $query->where('form_submissions.submitted_by', $filters['spg_id']))
            ->when($filters['from'] ?? null, fn (Builder $query, $from) => $query->whereDate('form_submissions.submitted_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, $to) => $query->whereDate('form_submissions.submitted_at', '<=', $to));

        $active = (clone $submissions)->where('form_submissions.status', SubmissionStatus::Submitted);

        $perDay = (clone $active)
            ->selectRaw('date(form_submissions.submitted_at) as day, count(*) as total')
            ->groupByRaw('date(form_submissions.submitted_at)')
            ->orderBy('day')
            ->get();

        $perLocation = (clone $active)
            ->join('assignments', 'assignments.id', '=', 'form_submissions.assignment_id')
            ->join('campaign_locations', 'campaign_locations.id', '=', 'assignments.location_id')
            ->selectRaw('campaign_locations.name as label, count(form_submissions.id) as total')
            ->groupBy('campaign_locations.id', 'campaign_locations.name')
            ->orderBy('campaign_locations.name')
            ->get();

        $perSpg = (clone $active)
            ->join('users', 'users.id', '=', 'form_submissions.submitted_by')
            ->selectRaw('users.name as label, count(form_submissions.id) as total')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        $assignmentQuery = Assignment::query()->visibleTo($viewer)
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, $id) => $query->where('campaign_id', $id));

        $custom = $this->customFieldMetrics($viewer, $filters);

        return [
            'totals' => [
                'submissions' => (clone $active)->count(),
                'voided' => (clone $submissions)->where('status', SubmissionStatus::Void)->count(),
                'qualified' => (clone $active)->where('qualified', true)->count(),
                'campaigns' => Campaign::query()->visibleTo($viewer)->count(),
            ],
            'assignments' => [
                'scheduled' => (clone $assignmentQuery)->where('status', AssignmentStatus::Scheduled)->count(),
                'active' => (clone $assignmentQuery)->where('status', AssignmentStatus::Active)->count(),
                'done' => (clone $assignmentQuery)->where('status', AssignmentStatus::Done)->count(),
                'cancelled' => (clone $assignmentQuery)->where('status', AssignmentStatus::Cancelled)->count(),
            ],
            'per_day' => $perDay,
            'per_location' => $perLocation,
            'per_spg' => $viewer->isSuperadmin() ? $perSpg : [],
            'custom_fields' => $custom,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function customFieldMetrics(User $viewer, array $filters): array
    {
        $values = SubmissionFieldValue::query()
            ->whereHas('submission', function (Builder $query) use ($viewer, $filters) {
                $query->visibleTo($viewer)
                    ->where('status', SubmissionStatus::Submitted)
                    ->when($filters['campaign_id'] ?? null, fn (Builder $inner, $id) => $inner->where('campaign_id', $id));
            })
            ->get()
            ->groupBy('field_key');

        return $values->map(function ($rows, $key) {
            $type = $rows->first()->field_type;
            $metric = [
                'key' => $key,
                'type' => $type->value,
            ];

            if ($type === FieldType::Number) {
                $metric['sum'] = round((float) $rows->sum('value_number'), 2);
                $metric['avg'] = round((float) $rows->avg('value_number'), 2);

                return $metric;
            }

            $metric['options'] = $rows
                ->flatMap(fn ($row) => explode('|', (string) $row->value_text))
                ->filter()
                ->countBy()
                ->map(fn ($total, $label) => ['label' => $label, 'total' => $total])
                ->values()
                ->all();

            return $metric;
        })->values()->all();
    }
}
