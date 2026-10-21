<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('tenant_api', function (\Illuminate\Http\Request $request) {
            $limit = 100;
            if (function_exists('tenant') && tenant()) {
                $limit = tenant()->plan->api_rate_limit ?? 100;
            }
            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });
    }
}
