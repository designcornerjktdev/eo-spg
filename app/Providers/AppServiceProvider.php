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

        // Public form submissions: generous enough for a busy booth on shared Wi-Fi, low enough to blunt spam.
        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by($request->ip().'|'.$request->route('token')));
    }
}
