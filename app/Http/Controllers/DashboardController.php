<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\Services\DashboardAggregator;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardAggregator $aggregator): Response
    {
        $user = $request->user();

        $filters = $request->only(['campaign_id', 'location_id', 'from', 'to']);

        return Inertia::render('dashboard', [
            'summary' => $aggregator->summarize($user, $filters),
            'filters' => $filters,
            'campaigns' => Campaign::query()->visibleTo($user)->orderBy('name')->get(['id', 'name']),
            'locations' => CampaignLocation::query()
                ->whereHas('campaign', fn ($query) => $query->visibleTo($user))
                ->orderBy('name')
                ->get(['id', 'name', 'campaign_id']),
        ]);
    }
}
