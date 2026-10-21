<?php

namespace App\Http\Controllers\Spg;

use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\StoreSubmissionRequest;
use App\Models\Assignment;
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

        return Inertia::render('spg/fill', [
            'assignment' => $assignment,
            'fillable' => $request->user()->isSuperadmin() || $assignment->isFillableNow(),
            'context' => [
                'tanggal' => now()->toDateString(),
                'area' => $assignment->location?->area,
                'lokasi' => $assignment->location?->name,
                'spg' => $assignment->user?->name,
            ],
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

        if ($request->boolean('add_another')) {
            return redirect()
                ->route('spg.tasks.fill', $assignment)
                ->with('success', 'Data tersimpan. Silakan isi baris berikutnya.');
        }

        return redirect()->route('spg.submissions.index')->with('success', 'Data tersimpan.');
    }
}
