<?php

namespace Tests\Feature\PublicForm;

use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class ConcurrentSubmitTest extends TestCase
{
    use BuildsCampaignWorld;
    use RefreshDatabase;

    public function test_a_repeated_submission_token_saves_one_entry(): void
    {
        ['location' => $location] = $this->world();

        $this->post('/f/'.$location->public_token, [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
            'submission_token' => 'token-1',
        ])->assertSessionHas('submitted', true);

        // Double tap or a network retry sends the same form again.
        $this->post('/f/'.$location->public_token, [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
            'submission_token' => 'token-1',
        ])->assertSessionHas('submitted', true);

        $this->submitPublicEntry($location);

        $this->assertSame(2, FormSubmission::query()->count());
        $this->assertSame(1, FormSubmission::query()->where('client_token', 'token-1')->count());
    }

    public function test_many_phones_behind_one_ip_are_not_throttled_together(): void
    {
        ['location' => $location] = $this->world();

        // 30 different browsers (fresh sessions) on the same venue Wi-Fi.
        foreach (range(1, 30) as $phone) {
            $this->flushSession();
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1']);
            $this->submitPublicEntry($location)->assertSessionHas('submitted', true);
        }

        $this->assertSame(30, FormSubmission::query()->count());
    }

    public function test_one_browser_is_still_rate_limited(): void
    {
        ['location' => $location] = $this->world();

        // Keep sending the session cookie, as a real browser does.
        $cookie = config('session.cookie');
        foreach (range(1, 10) as $attempt) {
            $response = $this->submitPublicEntry($location)->assertSessionHas('submitted', true);
            $this->withCookie($cookie, $response->getCookie($cookie)->getValue());
        }

        $this->submitPublicEntry($location)->assertTooManyRequests();
    }
}
