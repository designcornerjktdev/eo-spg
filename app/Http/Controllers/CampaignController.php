<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Enums\CampaignStatus;
use App\Http\Requests\Campaigns\CampaignRequest;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Campaign::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => in_array($request->query('status'), array_column(CampaignStatus::cases(), 'value'), true) ? $request->query('status') : null,
            'client_id' => $request->integer('client_id') ?: null,
        ];

        $campaigns = Campaign::query()
            ->visibleTo($request->user())
            ->with('client')
            ->withCount(['locations', 'assignments', 'submissions'])
            ->when($filters['search'] !== '', fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%'))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['client_id'] && $request->user()->isSuperadmin(), fn ($query) => $query->where('client_id', $filters['client_id']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
            'filters' => $filters,
            'clients' => $request->user()->isSuperadmin() ? Client::query()->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Campaign::class);

        return Inertia::render('campaigns/create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'client_id']),
        ]);
    }

    public function store(CampaignRequest $request, AuditLogger $audit): RedirectResponse
    {
        $campaign = DB::transaction(function () use ($request, $audit) {
            $campaign = Campaign::query()->create([
                ...$request->safe()->only(['client_id', 'name', 'starts_on', 'ends_on', 'notes']),
                'mask_identity_for_client' => $request->boolean('mask_identity_for_client', true),
                'status' => CampaignStatus::Draft,
            ]);

            $campaign->products()->sync($request->input('product_ids', []));
            $this->syncLocations($campaign, $request->input('locations', []));
            $audit->record($request->user(), 'campaign.created', $campaign, null, $campaign->only(['name', 'client_id', 'starts_on', 'ends_on']));

            return $campaign;
        });

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign created.');
    }

    public function show(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load(['client', 'products', 'locations', 'formTemplates.fields', 'assignments.user', 'assignments.location']);

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        $this->authorize('update', $campaign);

        $campaign->load(['products', 'locations']);

        return Inertia::render('campaigns/edit', [
            'campaign' => $campaign,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'client_id']),
            'deleteBlocker' => $this->deleteBlocker($campaign),
        ]);
    }

    public function update(CampaignRequest $request, Campaign $campaign, AuditLogger $audit): RedirectResponse
    {
        $old = $campaign->only(['name', 'starts_on', 'ends_on', 'status']);

        DB::transaction(function () use ($request, $campaign) {
            $campaign->update([
                ...$request->safe()->only(['client_id', 'name', 'starts_on', 'ends_on', 'notes']),
                'mask_identity_for_client' => $request->boolean('mask_identity_for_client', true),
            ]);
            $campaign->products()->sync($request->input('product_ids', []));
            $this->syncLocations($campaign, $request->input('locations', []));
        });

        $audit->record($request->user(), 'campaign.updated', $campaign, $old, $campaign->fresh()->only(['name', 'starts_on', 'ends_on', 'status']));

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    public function activate(Request $request, Campaign $campaign, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $old = ['status' => $campaign->status->value];
        $campaign->update(['status' => CampaignStatus::Active]);
        $audit->record($request->user(), 'campaign.activated', $campaign, $old, ['status' => CampaignStatus::Active->value]);

        return back()->with('success', 'Campaign activated.');
    }

    public function close(Request $request, Campaign $campaign, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $old = ['status' => $campaign->status->value];
        $campaign->update(['status' => CampaignStatus::Closed]);
        $audit->record($request->user(), 'campaign.closed', $campaign, $old, ['status' => CampaignStatus::Closed->value]);

        return back()->with('success', 'Campaign closed.');
    }

    public function destroy(Request $request, Campaign $campaign, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        if ($blocker = $this->deleteBlocker($campaign)) {
            throw ValidationException::withMessages(['campaign' => $blocker]);
        }

        $audit->record($request->user(), 'campaign.deleted', $campaign, $campaign->only(['name', 'client_id', 'status']));
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign '.$campaign->name.' deleted.');
    }

    /**
     * Only unused draft campaigns may be deleted; others are closed so reports stay intact.
     */
    private function deleteBlocker(Campaign $campaign): ?string
    {
        if ($campaign->status !== CampaignStatus::Draft) {
            return 'Only draft campaigns can be deleted. Close an active campaign instead.';
        }

        if ($campaign->assignments()->exists() || $campaign->submissions()->exists()) {
            return 'This campaign already has SPG assignments or entries, so it cannot be deleted.';
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $locations
     */
    private function syncLocations(Campaign $campaign, array $locations): void
    {
        $kept = [];

        foreach ($locations as $location) {
            $payload = [
                'name' => $location['name'],
                'area' => $location['area'] ?? null,
                'address' => $location['address'] ?? null,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
            ];

            $model = ! empty($location['id'])
                ? $campaign->locations()->whereKey($location['id'])->first()
                : null;

            if ($model) {
                $model->update($payload);
            } else {
                $model = $campaign->locations()->create($payload);
            }

            $kept[] = $model->id;
        }

        // Locations used by assignments must not be removed: the assignment -> location FK cascades,
        // which would also delete the assignments and their SPG entries.
        $removed = $campaign->locations()->whereNotIn('id', $kept);
        $used = (clone $removed)->whereHas('assignments')->pluck('name');

        if ($used->isNotEmpty()) {
            throw ValidationException::withMessages([
                'locations' => 'Location '.$used->join(', ').' is used by SPG assignments, so it cannot be removed.',
            ]);
        }

        $removed->delete();
    }
}
