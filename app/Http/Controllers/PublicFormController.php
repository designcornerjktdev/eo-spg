<?php

namespace App\Http\Controllers;

use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Models\CampaignLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, no-login form that consumers fill in through a campaign location's link or QR code.
 */
class PublicFormController extends Controller
{
    public function show(string $token): Response
    {
        $location = $this->location($token);
        $campaign = $location->campaign;
        $form = $campaign->currentForm()?->load('fields');

        return Inertia::render('public/form', [
            'token' => $location->public_token,
            'campaign' => $campaign->name,
            'location' => $location->name,
            'open' => $campaign->isAcceptingEntries(),
            // Only locations with a map point can check that the visitor is on site.
            'geofence' => $location->latitude !== null && $location->longitude !== null
                ? ['latitude' => $location->latitude, 'longitude' => $location->longitude, 'radius' => $location->radius_meters]
                : null,
            'form' => $form ? [
                'name' => $form->name,
                'requires_consent' => $form->requires_consent,
                'consent_text' => $form->consent_text,
                'fields' => $form->fields->map->only(['key', 'label', 'type', 'required', 'options', 'min_value', 'max_value'])->values(),
            ] : null,
        ]);
    }

    public function store(Request $request, string $token, SubmissionRecorder $recorder): RedirectResponse
    {
        $location = $this->location($token);

        $request->validate([
            'answers' => ['array'],
            'consent_accepted' => ['sometimes', 'boolean'],
            'position.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'position.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'position.accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Honeypot: real people never see this field, so a filled value means a bot. Pretend it worked.
        if (filled($request->input('website'))) {
            return back()->with('submitted', true);
        }

        // A browser without location support sends no position at all; treat it as "unknown".
        // Uploaded images arrive in the file bag, not the input bag.
        $answers = array_replace($request->input('answers', []), $request->file('answers', []));
        $recorder->record($location, $answers, $request->boolean('consent_accepted'), (array) $request->input('position'));

        return back()->with('submitted', true);
    }

    private function location(string $token): CampaignLocation
    {
        // whereHas('campaign') skips locations whose campaign was (soft) deleted.
        return CampaignLocation::query()
            ->where('public_token', $token)
            ->whereHas('campaign')
            ->with('campaign')
            ->firstOrFail();
    }
}
