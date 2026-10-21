<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('tenant.login');
        }

        // Parse allowed roles (supports both "admin,teacher" and multiple params)
        $allowed = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $subRole) {
                $trimmed = strtolower(trim($subRole));
                if ($trimmed !== '') {
                    $allowed[] = $trimmed;
                }
            }
        }

        $userType = strtolower((string) ($user->user_type ?? ''));

        // Check user_type match
        if (in_array($userType, $allowed, true)) {
            return $next($request);
        }

        // Check Spatie roles
        if (method_exists($user, 'roles')) {
            $userRoles = $user->roles->pluck('name')->map(fn($r) => strtolower((string) $r))->toArray();
            foreach ($allowed as $role) {
                if (in_array($role, $userRoles, true)) {
                    return $next($request);
                }
            }
        }

        // Unauthorized access
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to access this portal resource.'
            ], 403);
        }

        abort(403, 'Unauthorized portal access.');
    }
}
