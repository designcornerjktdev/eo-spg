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
    /**
     * Satu-satunya halaman SPG: tabel data assignment terpilih + form input.
     */
    public function index(Request $request): Response
    {
        $assignments = Assignment::query()
            ->visibleTo($request->user())
            ->with(['campaign', 'location', 'formTemplate'])
            ->latest('starts_at')
            ->get();

        $selected = $request->filled('assignment')
            ? Assignment::query()->findOrFail($request->integer('assignment'))
            : ($assignments->first(fn (Assignment $assignment) => $assignment->isFillableNow()) ?? $assignments->first());

        $props = [
            'assignments' => $assignments->map(fn (Assignment $assignment) => [
                'id' => $assignment->id,
                'campaign' => $assignment->campaign?->name,
                'location' => $assignment->location?->name,
                'fillable' => $assignment->isFillableNow(),
            ]),
            'assignment' => null,
        ];

        if ($selected) {
            $this->authorize('fill', $selected);
            $selected->load(['campaign', 'location', 'formTemplate.fields', 'user']);

            $ownSubmissions = fn () => FormSubmission::query()
                ->where('assignment_id', $selected->id)
                ->where('submitted_by', $request->user()->id);

            $props = [
                ...$props,
                'assignment' => $selected,
                'fillable' => $request->user()->isSuperadmin() || $selected->isFillableNow(),
                'context' => [
                    'date' => now()->toDateString(),
                    'area' => $selected->location?->area,
                    'location' => $selected->location?->name,
                    'spg' => $selected->user?->name,
                ],
                'submissions' => $ownSubmissions()
                    ->latest('submitted_at')
                    ->paginate(50)
                    ->withQueryString()
                    ->through(fn (FormSubmission $submission) => [
                        ...$submission->only(['id', 'submitted_at', 'status', 'answers', 'consent_accepted', 'void_reason']),
                        'can_correct' => $request->user()->isSpg() && $submission->isCorrectableBySubmitter(),
                    ]),
                'todayCount' => $ownSubmissions()->whereDate('submitted_at', now()->toDateString())->count(),
            ];
        }

        return Inertia::render('spg/index', $props);
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment, SubmissionRecorder $recorder): RedirectResponse
    {
        $recorder->record(
            $assignment,
            $request->user(),
            $request->input('answers', []),
            $request->boolean('consent_accepted'),
        );

        // Flag add_another membuat form tetap terbuka di frontend.
        return redirect()
            ->route('spg.tasks.index', ['assignment' => $assignment->id])
            ->with('success', $request->boolean('add_another') ? 'Data tersimpan. Silakan isi baris berikutnya.' : 'Data tersimpan.');
    }
}
