<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return route('tenant.login');
            }
            return route('login');
        });

        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return route('tenant.home');
            }
            return route('central.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException $e, \Illuminate\Http\Request $request) {
            $centralDomain = config('tenancy.central_domains', ['localhost'])[0] ?? 'localhost';
            $port = $request->getPort();
            $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';
            $protocol = $request->getScheme();
            $centralUrl = "{$protocol}://{$centralDomain}{$portSuffix}";

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'School portal not found or subdomain incorrect.',
                    'subdomain' => $request->getHost(),
                    'reason' => 'not_found',
                ], 404);
            }

            return \Inertia\Inertia::render('Errors/TenantUnavailable', [
                'subdomain' => $request->getHost(),
                'reason' => 'not_found',
                'centralUrl' => $centralUrl,
                'message' => "No active school portal was found at '{$request->getHost()}'. Please check for any spelling mistakes in the web address."
            ])->toResponse($request)->setStatusCode(404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost', '192.168.100.8']);
            $currentHost = $request->getHost();

            if (!in_array($currentHost, $centralDomains)) {
                $centralDomain = $centralDomains[0] ?? 'localhost';
                $port = $request->getPort();
                $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';
                $protocol = $request->getScheme();
                $centralUrl = "{$protocol}://{$centralDomain}{$portSuffix}";

                if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
                    $tenant = tenancy()->tenant;
                    if ($request->wantsJson() || $request->is('api/*')) {
                        return response()->json(['error' => 'Endpoint not found on this school portal.'], 404);
                    }
                    return \Inertia\Inertia::render('Errors/TenantUnavailable', [
                        'subdomain' => $currentHost,
                        'reason' => 'page_not_found',
                        'tenantName' => $tenant->name ?? 'School Portal',
                        'centralUrl' => $centralUrl,
                        'message' => "The requested URL '{$request->path()}' does not exist on this school's portal."
                    ])->toResponse($request)->setStatusCode(404);
                } else {
                    if ($request->wantsJson() || $request->is('api/*')) {
                        return response()->json(['error' => 'School portal not found or subdomain incorrect.'], 404);
                    }
                    return \Inertia\Inertia::render('Errors/TenantUnavailable', [
                        'subdomain' => $currentHost,
                        'reason' => 'not_found',
                        'centralUrl' => $centralUrl,
                        'message' => "No active school portal was found at '{$currentHost}'. Please check for any spelling mistakes in the web address."
                    ])->toResponse($request)->setStatusCode(404);
                }
            }
        });
    })->create();
