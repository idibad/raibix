<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\FeeChallan;
use App\Models\Student;
use App\Models\Assignment;
use App\Policies\FeeChallanPolicy;
use App\Policies\StudentPolicy;
use App\Policies\AssignmentPolicy;

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
        Gate::policy(FeeChallan::class, FeeChallanPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);

        \Illuminate\Support\Facades\RateLimiter::for('tenant_api', function (\Illuminate\Http\Request $request) {
            $limit = 100;
            if (function_exists('tenant') && tenant()) {
                $limit = tenant()->plan->api_rate_limit ?? 100;
            }
            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });
    }
}
