<?php

namespace Tests\Feature\Security;

use App\Models\FormSubmission;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    protected function tearDown(): void
    {
        // TrustProxies::at() is static; never leak a trusted setting into other tests.
        TrustProxies::flushState();
        parent::tearDown();
    }

    private function probeRoute(): void
    {
        Route::get('/_probe', fn () => ['ip' => request()->ip(), 'scheme' => request()->getScheme(), 'host' => request()->getHost()]);
    }

    public function test_forwarded_headers_are_ignored_by_default(): void
    {
        $this->probeRoute();

        $this->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example'])
            ->get('/_probe')
            ->assertJson(['ip' => '127.0.0.1', 'scheme' => 'http', 'host' => 'localhost']);
    }

    public function test_spoofed_client_ip_cannot_bypass_the_public_form_rate_limit(): void
    {
        $world = $this->world();

        // A cookie-less client with a new fake X-Forwarded-For each time still counts as one IP.
        for ($i = 0; $i < AppServiceProvider::PUBLIC_FORM_PER_IP; $i++) {
            $this->withHeader('X-Forwarded-For', '198.51.'.intdiv($i, 250).'.'.($i % 250))->submitPublicEntry($world['location'])->assertSessionHasNoErrors();
        }
        $this->withHeader('X-Forwarded-For', '198.51.100.99')->submitPublicEntry($world['location'])->assertTooManyRequests();

        $this->assertSame(AppServiceProvider::PUBLIC_FORM_PER_IP, FormSubmission::query()->count());
    }

    public function test_configured_proxies_are_trusted(): void
    {
        config(['app.trusted_proxies' => '127.0.0.1, 10.0.0.0/8']);
        $this->app->getProvider(AppServiceProvider::class)->boot();
        $this->probeRoute();

        $this->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'tunnel.example'])
            ->get('/_probe')
            ->assertJson(['ip' => '203.0.113.9', 'scheme' => 'https', 'host' => 'tunnel.example']);
    }
}
