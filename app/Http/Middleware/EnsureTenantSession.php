<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantSession
{
    /**
     * Handle an incoming request.
     * Ensure that school tenant portals only accept authentic, tenant-scoped user sessions.
     * Prevents any central Super Admin session or other tenant's session from cross-pollinating into this school.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!function_exists('tenancy') || !tenancy()->initialized) {
            return $next($request);
        }

        $tenantId = tenant()->getTenantKey();

        // 1. If the session has a central context tag without a tenant authentication tag
        if ($request->session()->get('auth_context') === 'central' || ($request->session()->get('is_super_admin') === true && $request->session()->get('_tenant_id') !== $tenantId)) {
            // Central session arrived at tenant domain: do not auto-login as tenant user
            Auth::logout();
            $request->session()->forget([
                'login_web_' . sha1(\App\Models\User::class),
                'auth_context',
                'is_super_admin',
            ]);
        }

        // 2. If the session is tagged for a DIFFERENT tenant ID, reject immediately
        if ($request->session()->has('_tenant_id') && $request->session()->get('_tenant_id') !== $tenantId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Session mismatch. This active session belongs to a different school campus.');
        }

        return $next($request);
    }
}
