<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Public form submissions: generous enough for a busy booth on shared Wi-Fi, low enough to blunt spam.
        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by($request->ip().'|'.$request->route('token')));
    }
}
