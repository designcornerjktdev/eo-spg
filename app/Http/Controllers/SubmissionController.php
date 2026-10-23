<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\Services\IdentityMasker;
use App\Domain\Reporting\Services\SubmissionExporter;
use App\Domain\Submissions\Services\SubmissionVoider;
use App\Http\Requests\Submissions\VoidSubmissionRequest;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Request $request, IdentityMasker $masker): Response
    {
        $this->authorize('viewAny', FormSubmission::class);

        $submissions = $this->filtered($request)
            ->paginate(20)
            ->withQueryString();

        $submissions->setCollection(
            $submissions->getCollection()->map(fn (FormSubmission $submission) => $this->present($request->user(), $submission, $masker))
        );

        return Inertia::render('submissions/index', [
            'submissions' => $submissions,
            'filters' => $request->only(['campaign_id', 'location_id', 'from', 'to', 'q']),
            'campaigns' => Campaign::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
            'locations' => CampaignLocation::query()
                ->whereHas('campaign', fn ($query) => $query->visibleTo($request->user()))
                ->orderBy('name')
                ->get(['id', 'name', 'campaign_id']),
        ]);
    }

    public function show(Request $request, FormSubmission $submission, IdentityMasker $masker): Response
    {
        $this->authorize('view', $submission);

        $submission->load(['formTemplate.fields', 'location', 'campaign']);

        return Inertia::render('submissions/show', [
            'submission' => $this->present($request->user(), $submission, $masker),
        ]);
    }

    public function void(VoidSubmissionRequest $request, FormSubmission $submission, SubmissionVoider $voider)
    {
        $voider->void($submission, $request->user(), $request->string('void_reason')->toString());

        return back()->with('success', 'Entry voided.');
    }

    public function export(Request $request, SubmissionExporter $exporter): StreamedResponse
    {
        $this->authorize('export', FormSubmission::class);

        $submissions = $this->filtered($request)->get();

        return $exporter->download($request->user(), $submissions);
    }

    private function filtered(Request $request)
    {
        $user = $request->user();

        return FormSubmission::query()
            ->visibleTo($user)
            ->with(['formTemplate.fields', 'location', 'campaign'])
            ->when($request->campaign_id, fn ($query, $id) => $query->where('campaign_id', $id))
            ->when($request->location_id, fn ($query, $id) => $query->where('location_id', $id))
            ->when($request->from, fn ($query, $from) => $query->whereDate('submitted_at', '>=', $from))
            ->when($request->to, fn ($query, $to) => $query->whereDate('submitted_at', '<=', $to))
            ->when($request->q, function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('location', fn ($location) => $location->where('name', 'like', '%'.$q.'%'))
                        ->orWhereHas('campaign', fn ($campaign) => $campaign->where('name', 'like', '%'.$q.'%'));
                });
            })
            ->latest('submitted_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $viewer, FormSubmission $submission, IdentityMasker $masker): array
    {
        $fields = $submission->formTemplate?->fields ?? collect();
        $visibleFields = $fields
            ->filter(fn ($field) => ! $viewer->isClient() || $field->visible_to_client)
            ->values();

        return [
            'id' => $submission->id,
            'campaign' => $submission->campaign?->only(['id', 'name', 'mask_identity_for_client']),
            'location' => $submission->location?->only(['id', 'name', 'area']),
            'distance_meters' => $submission->distance_meters,
            'location_accuracy_meters' => $submission->location_accuracy_meters,
            'submitted_at' => $submission->submitted_at,
            'status' => $submission->status,
            'qualified' => $submission->qualified,
            'void_reason' => $viewer->isSuperadmin() ? $submission->void_reason : null,
            'answers' => $masker->maskAnswers($viewer, $submission, $submission->answers ?? [], $submission->formTemplate),
            'fields' => $visibleFields,
            'can_void' => $viewer->can('void', $submission) && ! $submission->isVoid(),
        ];
    }
}
