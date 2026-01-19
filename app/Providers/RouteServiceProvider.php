<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    // Redirect path after authentication; align with SPA root.
    public const HOME = '/';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('attempts', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('presentation_id');
            return Limit::perMinute(120)->by($key);
        });

        RateLimiter::for('presentations', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
