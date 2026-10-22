<?php

namespace App\Http\Controllers;

use App\Domain\Assignments\Services\AssignmentScheduler;
use App\Domain\Audit\Services\AuditLogger;
use App\Enums\AssignmentStatus;
use App\Http\Requests\Assignments\AssignmentRequest;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentScheduler $scheduler) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assignment::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => in_array($request->query('status'), array_column(AssignmentStatus::cases(), 'value'), true) ? $request->query('status') : null,
            'campaign_id' => $request->integer('campaign_id') ?: null,
        ];

        $assignments = Assignment::query()
            ->visibleTo($request->user())
            ->with(['campaign', 'location', 'user', 'formTemplate'])
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$filters['search'].'%'))
                ->orWhereHas('location', fn ($location) => $location->where('name', 'like', '%'.$filters['search'].'%'))))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['campaign_id'], fn ($query, $campaignId) => $query->where('campaign_id', $campaignId))
            ->latest('starts_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('assignments/index', [
            'assignments' => $assignments,
            'filters' => $filters,
            'campaigns' => Campaign::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Assignment::class);

        $campaigns = Campaign::query()->with(['locations', 'formTemplates'])->orderBy('name')->get();

        return Inertia::render('assignments/create', [
            'campaigns' => $campaigns,
            'spgs' => User::query()->where('role', 'spg')->orderBy('name')->get(['id', 'name']),
            'selectedCampaignId' => $request->integer('campaign_id') ?: null,
        ]);
    }

    public function store(AssignmentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $assignment = $this->scheduler->create([
            ...$request->validated(),
            'status' => AssignmentStatus::Scheduled,
        ]);
        $audit->record($request->user(), 'assignment.created', $assignment);

        return redirect()->route('assignments.index')->with('success', 'Assignment berhasil dibuat.');
    }

    public function edit(Assignment $assignment): Response
    {
        $this->authorize('update', $assignment);

        return Inertia::render('assignments/edit', [
            'assignment' => $assignment->load(['campaign', 'location', 'formTemplate', 'user'])->loadCount('submissions'),
            'deleteBlocker' => $this->deleteBlocker($assignment),
            'campaigns' => Campaign::query()->with(['locations', 'formTemplates'])->orderBy('name')->get(),
            'spgs' => User::query()->where('role', 'spg')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(AssignmentRequest $request, Assignment $assignment, AuditLogger $audit): RedirectResponse
    {
        $old = $assignment->only(['user_id', 'starts_at', 'ends_at', 'status']);
        $assignment = $this->scheduler->update($assignment, $request->validated());
        $audit->record($request->user(), 'assignment.updated', $assignment, $old, $assignment->only(['user_id', 'starts_at', 'ends_at', 'status']));

        return redirect()->route('assignments.index')->with('success', 'Assignment diperbarui.');
    }

    /**
     * Penugasan yang sudah punya data dibatalkan (status cancelled), bukan dihapus; jadwal SPG jadi bebas lagi.
     */
    public function cancel(Request $request, Assignment $assignment, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $assignment);

        if ($assignment->status === AssignmentStatus::Cancelled) {
            throw ValidationException::withMessages(['assignment' => 'Penugasan ini sudah dibatalkan.']);
        }

        $old = ['status' => $assignment->status->value];
        $assignment->update(['status' => AssignmentStatus::Cancelled]);
        $audit->record($request->user(), 'assignment.cancelled', $assignment, $old, ['status' => AssignmentStatus::Cancelled->value]);

        return redirect()->route('assignments.index')->with('success', 'Penugasan dibatalkan.');
    }

    public function destroy(Request $request, Assignment $assignment, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        if ($blocker = $this->deleteBlocker($assignment)) {
            throw ValidationException::withMessages(['assignment' => $blocker]);
        }

        $audit->record($request->user(), 'assignment.deleted', $assignment, $assignment->only(['campaign_id', 'location_id', 'user_id', 'starts_at', 'ends_at']));
        $assignment->delete();

        return redirect()->route('assignments.index')->with('success', 'Penugasan dihapus.');
    }

    /**
     * FK submission ke assignment memakai cascade, jadi penugasan yang sudah punya data tidak boleh dihapus.
     */
    private function deleteBlocker(Assignment $assignment): ?string
    {
        return $assignment->submissions()->exists()
            ? 'Penugasan ini sudah punya data dari SPG, jadi tidak bisa dihapus. Batalkan saja penugasannya.'
            : null;
    }
}
