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
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentScheduler $scheduler) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assignment::class);

        $assignments = Assignment::query()
            ->visibleTo($request->user())
            ->with(['campaign', 'location', 'user', 'formTemplate'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('assignments/index', [
            'assignments' => $assignments,
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
            'assignment' => $assignment->load(['campaign', 'location', 'formTemplate', 'user']),
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
}
