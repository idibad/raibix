<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogTenantRequests
{
    public function handle(Request $request, Closure $next)
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : 'no-session';
        $authBefore = auth()->check() ? auth()->id() : '0';
        $response = $next($request);
        $authAfter = auth()->check() ? auth()->id() : '0';
        $status = $response->getStatusCode();
        $location = $response->headers->get('Location', 'none');
        $isInertia = $request->header('X-Inertia') ? 'YES' : 'NO';
        Log::info("[TENANT REQ] {$request->method()} {$request->path()} | Inertia: {$isInertia} | Session: {$sessionId} | Auth: {$authBefore}->{$authAfter} | Status: {$status} | Location: {$location}");
        return $response;
    }
}
