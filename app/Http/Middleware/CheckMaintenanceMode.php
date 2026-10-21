<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SaaSSetting;
use Inertia\Inertia;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $maintenanceMode = false;
        
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                $maintenanceMode = tenancy()->central(function () {
                    return (bool) (SaaSSetting::first()?->maintenance_mode);
                });
            } else {
                $maintenanceMode = (bool) (SaaSSetting::first()?->maintenance_mode);
            }
        } catch (\Throwable $e) {
            // Fallback in case table doesn't exist yet during migrations
            $maintenanceMode = false;
        }

        if ($maintenanceMode) {
            // Bypass maintenance mode for:
            // 1. Super Admin paths on central domain
            // 2. Already logged in central super admin
            $isCentralDomain = in_array($request->getHost(), config('tenancy.central_domains', ['localhost', '127.0.0.1', '192.168.100.8']));
            $isSuperAdminPath = $request->is('super-admin*') || $request->is('login') || $request->is('logout');
            
            // Check central auth
            $isSuperAdmin = false;
            if ($isCentralDomain) {
                $isSuperAdmin = auth()->guard('web')->check();
            }

            if (!$isSuperAdminPath && !$isSuperAdmin) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => 'Platform Under Maintenance',
                        'message' => 'LMS Cloud is currently undergoing scheduled maintenance. Please try again later.'
                    ], 503);
                }

                return Inertia::render('Errors/Maintenance', [
                    'message' => 'LMS Cloud is currently undergoing scheduled system upgrades and maintenance to improve services. We will be back online shortly.'
                ])->toResponse($request)->setStatusCode(503);
            }
        }

        return $next($request);
    }
}
