<?php

namespace App\Http\Controllers\Spg;

use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\StoreSubmissionRequest;
use App\Models\Assignment;
use App\Models\FormSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $assignments = Assignment::query()
            ->visibleTo($request->user())
            ->with(['campaign.client', 'location', 'formTemplate', 'campaign.products'])
            ->latest('starts_at')
            ->get()
            ->map(fn (Assignment $assignment) => [
                ...$assignment->toArray(),
                'fillable' => $assignment->isFillableNow(),
            ]);

        return Inertia::render('spg/tasks', [
            'assignments' => $assignments,
        ]);
    }

    public function fill(Request $request, Assignment $assignment): Response
    {
        $this->authorize('fill', $assignment);

        $assignment->load(['campaign.products', 'location', 'formTemplate.fields', 'user']);

        $submissions = FormSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('submitted_by', $request->user()->id)
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('spg/fill', [
            'assignment' => $assignment,
            'fillable' => $request->user()->isSuperadmin() || $assignment->isFillableNow(),
            'context' => [
                'date' => now()->toDateString(),
                'area' => $assignment->location?->area,
                'location' => $assignment->location?->name,
                'spg' => $assignment->user?->name,
            ],
            'submissions' => $submissions,
            'todayCount' => FormSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->where('submitted_by', $request->user()->id)
                ->whereDate('submitted_at', now()->toDateString())
                ->count(),
        ]);
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment, SubmissionRecorder $recorder): RedirectResponse
    {
        $recorder->record(
            $assignment,
            $request->user(),
            $request->input('answers', []),
            $request->boolean('consent_accepted'),
        );

        // Kembali ke daftar data assignment; flag add_another membuat form pop-up tetap terbuka di frontend.
        return redirect()
            ->route('spg.tasks.fill', $assignment)
            ->with('success', $request->boolean('add_another') ? 'Data tersimpan. Silakan isi baris berikutnya.' : 'Data tersimpan.');
    }
}
