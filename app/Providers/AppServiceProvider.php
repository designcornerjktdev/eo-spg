<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Public form submits per minute, per link: one browser, and one IP (shared by a whole booth on Wi-Fi). */
    public const PUBLIC_FORM_PER_BROWSER = 10;

    public const PUBLIC_FORM_PER_IP = 300;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Read from config (not env) so it still works after `php artisan config:cache`.
        $proxies = config('app.trusted_proxies');
        $proxies
            ? TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)))
            : TrustProxies::flushState();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Public form submissions. Many phones at one booth share an IP (venue Wi-Fi, carrier NAT), so the tight
        // limit is per browser session; the per-IP limit only stops floods from clients that drop cookies.
        RateLimiter::for('public-form', fn (Request $request) => [
            Limit::perMinute(self::PUBLIC_FORM_PER_BROWSER)->by('session|'.$request->session()->getId().'|'.$request->route('token')),
            Limit::perMinute(self::PUBLIC_FORM_PER_IP)->by('ip|'.$request->ip().'|'.$request->route('token')),
        ]);
    }
}
