<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request for a tenant route.
     * Checks if the initialized tenant is active. If suspended or inactive, displays our rich error handling page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            $tenant = tenancy()->tenant;

            $data = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '[]', true) ?? []);
            $status = $tenant->status ?? ($data['status'] ?? 'active');

            if ($status !== 'active') {
                $requestHost = $request->getHost();
                $centralDomains = config('tenancy.central_domains', ['localhost']);
                $centralDomain = in_array($requestHost, $centralDomains) ? $requestHost : (parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost');
                $port = $request->getPort();
                $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';
                $protocol = $request->getScheme();
                $centralUrl = "{$protocol}://{$centralDomain}{$portSuffix}";

                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => "School portal is currently {$status}. Access is temporarily disabled.",
                        'subdomain' => $request->getHost(),
                        'reason' => $status,
                        'status' => ucfirst($status),
                    ], 403);
                }

                return Inertia::render('Errors/TenantUnavailable', [
                    'subdomain' => $request->getHost(),
                    'reason' => $status,
                    'status' => ucfirst($status),
                    'tenantName' => $tenant->name ?? 'School Portal',
                    'centralUrl' => $centralUrl,
                    'message' => "This school portal (`{$tenant->name}`) is currently marked as '{$status}'. Access to student, teacher, and administrative dashboards has been temporarily paused until service status is restored."
                ])->toResponse($request)->setStatusCode(403);
            }
        }

        return $next($request);
    }
}
