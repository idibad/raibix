<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenantData = null;
        if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            $t = tenancy()->tenant;
            $dataBag = is_array($t->data) ? $t->data : (json_decode($t->data ?? '{}', true) ?? []);
            $tenantData = [
                'id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'logo' => $t->logo ?? null,
                'cover_color' => $t->cover_color ?? ($dataBag['cover_color'] ?? '#1A73E8'),
                'secondary_color' => $t->secondary_color ?? ($dataBag['secondary_color'] ?? '#0F172A'),
                'accent_color' => $t->accent_color ?? ($dataBag['accent_color'] ?? '#3B82F6'),
                'domain' => $t->domains()->first()?->domain ?? null,
            ];
        }

        $platformName = config('app.name', 'Raibix');

        return array_merge(parent::share($request), [
            'platformName' => $platformName,
            'appName' => $platformName,
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'user_type' => $request->user()->user_type,
                ] : null,
            ],
            'tenant' => $tenantData,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'requires_2fa' => fn () => (bool) ($request->session()->get('requires_2fa') || $request->session()->has('2fa_user_id')),
                'two_factor_email' => fn () => $request->session()->get('two_factor_email') ?: $request->session()->get('2fa_user_email'),
            ],
        ]);
    }
}
