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
            'answers' => ['required', 'array'],
            'consent_accepted' => ['sometimes', 'boolean'],
        ]);

        // Honeypot: real people never see this field, so a filled value means a bot. Pretend it worked.
        if (filled($request->input('website'))) {
            return back()->with('submitted', true);
        }

        $recorder->record($location, $request->input('answers', []), $request->boolean('consent_accepted'));

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
