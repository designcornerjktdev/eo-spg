<?php

namespace App\Http\Controllers\Spg;

use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Enums\SubmissionStatus;
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
     * Daftar tugas SPG; yang sedang aktif ditampilkan paling atas.
     */
    public function index(Request $request): Response
    {
        $assignments = Assignment::query()
            ->visibleTo($request->user())
            ->with(['campaign', 'location'])
            ->withCount(['submissions as my_count' => fn ($query) => $query
                ->where('submitted_by', $request->user()->id)
                ->where('status', '!=', SubmissionStatus::Void)])
            ->latest('starts_at')
            ->get()
            ->map(fn (Assignment $assignment) => [
                'id' => $assignment->id,
                'campaign' => $assignment->campaign?->name,
                'location' => $assignment->location?->name,
                'area' => $assignment->location?->area,
                'starts_at' => $assignment->starts_at,
                'ends_at' => $assignment->ends_at,
                'fillable' => $assignment->isFillableNow(),
                'my_count' => $assignment->my_count,
            ])
            ->sortByDesc('fillable')
            ->values();

        return Inertia::render('spg/index', [
            'assignments' => $assignments,
        ]);
    }

    /**
     * Tabel data milik SPG untuk satu tugas + form input.
     */
    public function show(Request $request, Assignment $assignment): Response
    {
        $this->authorize('fill', $assignment);
        $assignment->load(['campaign', 'location', 'formTemplate.fields', 'user']);

        $ownSubmissions = fn () => FormSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('submitted_by', $request->user()->id);

        return Inertia::render('spg/show', [
            'assignment' => $assignment,
            'fillable' => $request->user()->isSuperadmin() || $assignment->isFillableNow(),
            'context' => [
                'date' => now()->toDateString(),
                'area' => $assignment->location?->area,
                'location' => $assignment->location?->name,
                'spg' => $assignment->user?->name,
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

        // Flag add_another membuat form tetap terbuka di frontend.
        return redirect()
            ->route('spg.tasks.show', $assignment)
            ->with('success', $request->boolean('add_another') ? 'Data tersimpan. Silakan isi baris berikutnya.' : 'Data tersimpan.');
    }
}
