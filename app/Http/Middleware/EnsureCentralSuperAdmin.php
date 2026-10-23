<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SaaSActivityLog;

class EnsureCentralSuperAdmin
{
    /**
     * Handle an incoming request.
     * Ensure that only legitimate Central Super Admins can access /super-admin routes.
     * Strictly blocks any active school tenant session from leaking into or accessing Central Super Admin.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Must never execute inside a tenant database context
        if (function_exists('tenancy') && tenancy()->initialized) {
            abort(403, 'Tenant context is forbidden from accessing Central Super Admin resources.');
        }

        // 2. Must be accessed on a central domain
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost', '192.168.100.8']);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost && !in_array($appHost, $centralDomains, true)) {
            $centralDomains[] = $appHost;
        }

        if (!in_array($request->getHost(), $centralDomains, true)) {
            abort(403, 'Central Super Admin portal is only accessible via central apex domains. Host: ' . $request->getHost());
        }

        // 3. User must be authenticated
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // 4. Session must not contain any tenant scoping or tenant auth context
        if ($request->session()->has('_tenant_id') || $request->session()->get('auth_context') === 'tenant') {
            try {
                SaaSActivityLog::create([
                    'event_title' => 'Cross-Session Collision Blocked',
                    'description' => "Blocked active school tenant session [{$request->session()->get('_tenant_id')}] attempting to access Super Admin dashboard from IP {$request->ip()}.",
                    'event_type' => 'warning',
                    'actor_name' => $user->name ?? 'Unknown',
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                // Ignore log storage errors if database is in middle of transaction
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Access denied. Active school tenant sessions cannot access the Central Super Admin dashboard. Please log in with your Super Admin credentials.');
        }

        // 5. Verify the user is not a school tenant user masquerading with ID 1
        // In tenant databases, users have user_type defined ('student', 'parent', 'teacher', 'staff', 'librarian', 'accountant', 'registrar', 'principal')
        // In Central, only authentic platform super admins exist.
        if (isset($user->user_type) && in_array($user->user_type, ['student', 'parent', 'teacher', 'staff', 'librarian', 'accountant', 'registrar'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Unauthorized. Tenant role accounts cannot access the Central Super Admin portal.');
        }

        return $next($request);
    }
}
