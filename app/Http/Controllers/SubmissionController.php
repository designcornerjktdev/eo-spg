<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\Services\IdentityMasker;
use App\Domain\Reporting\Services\SubmissionXlsxExporter;
use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Domain\Submissions\Services\SubmissionVoider;
use App\Enums\FieldType;
use App\Http\Requests\Submissions\VoidSubmissionRequest;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            // Drop empty filters so links built from them never carry "null" values.
            'filters' => array_filter($request->only(['campaign_id', 'location_id', 'from', 'to', 'q']), 'filled'),
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

    /**
     * Streams an uploaded image answer to staff who can see the entry and that field.
     */
    public function file(Request $request, FormSubmission $submission, string $field)
    {
        $this->authorize('view', $submission);

        $definition = $submission->formTemplate?->fields->firstWhere('key', $field);
        abort_unless($definition && $definition->type === FieldType::Image, 404);
        abort_if($request->user()->isClient() && ! $definition->visible_to_client, 403);

        $path = $submission->answers[$field] ?? null;
        $disk = Storage::disk(SubmissionRecorder::IMAGE_DISK);
        abort_unless(is_string($path) && $disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => 'private, max-age=3600']);
    }

    public function void(VoidSubmissionRequest $request, FormSubmission $submission, SubmissionVoider $voider)
    {
        $voider->void($submission, $request->user(), $request->string('void_reason')->toString());

        return back()->with('success', 'Entry voided.');
    }

    /**
     * Excel export of the filtered entries, with photo answers as thumbnails.
     */
    public function export(Request $request, SubmissionXlsxExporter $exporter): StreamedResponse
    {
        $this->authorize('export', FormSubmission::class);

        return $exporter->download($request->user(), $this->filtered($request)->get());
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
     * Replaces stored image paths with viewable links.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function withFileUrls(FormSubmission $submission, array $answers): array
    {
        foreach ($submission->formTemplate?->fields ?? [] as $field) {
            if ($field->type === FieldType::Image && filled($answers[$field->key] ?? null)) {
                $answers[$field->key] = $submission->fileUrl($field->key);
            }
        }

        return $answers;
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
            'void_reason' => $viewer->isStaff() ? $submission->void_reason : null,
            'answers' => $this->withFileUrls($submission, $masker->maskAnswers($viewer, $submission, $submission->answers ?? [], $submission->formTemplate)),
            'fields' => $visibleFields,
            'can_void' => $viewer->can('void', $submission) && ! $submission->isVoid(),
        ];
    }
}
