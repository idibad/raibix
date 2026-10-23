<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ScopeSessionCookie
{
    /**
     * Handle an incoming request.
     * Enforce separate, isolated session cookies for Central vs each Tenant so their browser
     * sessions never collide, conflict, or overwrite each other.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost', '192.168.100.8']);

        $isCentral = in_array($host, $centralDomains, true);

        if ($isCentral) {
            config(['session.cookie' => 'raibix_central_session']);
        } else {
            // Extract clean subdomain from tenant host to isolate each tenant's session cookie
            $parts = explode('.', $host);
            $subdomain = $parts[0] ?? 'tenant';
            $cleanSubdomain = preg_replace('/[^a-zA-Z0-9_-]/', '_', $subdomain);
            config(['session.cookie' => 'raibix_tenant_' . $cleanSubdomain . '_session']);
        }

        return $next($request);
    }
}
