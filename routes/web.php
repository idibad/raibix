<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\SuperAdminController;
use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\Central\SaaSCheckoutController;
use App\Http\Controllers\Central\HealthCheckController;

$centralDomains = config('tenancy.central_domains', []);
$currentHost = request()->getHost();
$domain = in_array($currentHost, $centralDomains)
    ? $currentHost
    : (parse_url(config('app.url'), PHP_URL_HOST) ?? $centralDomains[0] ?? 'localhost');

Route::get('/api/health', [HealthCheckController::class, 'check'])->name('central.health');
Route::get('/health', [HealthCheckController::class, 'check'])->name('central.health.alias');

Route::get('/api/check-subdomain', function (\Illuminate\Http\Request $request) {
    $query = trim($request->query('q') ?? $request->query('subdomain') ?? '');
    if (strlen($query) < 2) {
        return response()->json(['results' => []])->header('Access-Control-Allow-Origin', '*');
    }
    
    $domains = \Stancl\Tenancy\Database\Models\Domain::with('tenant')
        ->where('domain', 'like', "%{$query}%")
        ->orWhereHas('tenant', function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('id', 'like', "%{$query}%");
        })
        ->take(5)
        ->get()
        ->map(function ($d) use ($request) {
            $tenant = $d->tenant;
            $data = is_array($tenant?->data) ? $tenant->data : (json_decode($tenant?->data ?? '[]', true) ?? []);
            $status = $tenant?->status ?? ($data['status'] ?? 'active');
            $port = $request->getPort();
            $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';
            $protocol = $request->getScheme();
            return [
                'domain' => $d->domain,
                'url' => "{$protocol}://{$d->domain}{$portSuffix}",
                'name' => $tenant?->name ?? $d->domain,
                'status' => $status,
            ];
        });

    return response()->json(['results' => $domains])->header('Access-Control-Allow-Origin', '*');
})->name('central.api.check-subdomain');

Route::domain($domain)->group(function () {
    // Public SaaS Landing Page & School Registration
    Route::get('/', [SuperAdminController::class, 'landing'])->name('central.landing');
    Route::get('/privacy', [SuperAdminController::class, 'privacy'])->name('central.privacy');
    Route::get('/privacy-policy', [SuperAdminController::class, 'privacy'])->name('central.privacy.alias');
    Route::get('/terms', [SuperAdminController::class, 'terms'])->name('central.terms');
    Route::get('/terms-of-service', [SuperAdminController::class, 'terms'])->name('central.terms.alias');
    Route::get('/security', [SuperAdminController::class, 'security'])->name('central.security');
    Route::get('/security-privacy', [SuperAdminController::class, 'security'])->name('central.security.alias');
    Route::post('/tenants', [SuperAdminController::class, 'storeTenant'])->name('central.tenants.store');
    Route::post('/inquiries', [SuperAdminController::class, 'storeInquiry'])->name('central.inquiries.store');

    // SaaS Subscription Checkout & Payment
    Route::get('/checkout/{plan_code}', [SaaSCheckoutController::class, 'showCheckout'])->name('central.checkout.show');
    Route::post('/checkout', [SaaSCheckoutController::class, 'processCheckout'])->name('central.checkout.process');
    Route::get('/checkout-success', [SaaSCheckoutController::class, 'showCheckoutSuccess'])->name('central.checkout.success');
    Route::post('/checkout/add-user', [SaaSCheckoutController::class, 'addFirstUser'])->name('central.checkout.add_user');

    // Super Admin Authentication Routes (with 2FA)
    Route::get('/super-admin/login', [CentralAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/super-admin/login', [CentralAuthController::class, 'login'])->name('central.login.post');
    Route::post('/super-admin/login/verify-2fa', [CentralAuthController::class, 'verify2fa'])->name('central.login.verify2fa');
    Route::post('/super-admin/login/resend-2fa', [CentralAuthController::class, 'resend2fa'])->name('central.login.resend2fa');
    Route::post('/super-admin/login/cancel-2fa', [CentralAuthController::class, 'cancel2fa'])->name('central.login.cancel2fa');
    Route::post('/super-admin/logout', [CentralAuthController::class, 'logout'])->name('central.logout');

    // Protected Super Admin Portal Routes
    Route::middleware('auth:web')->group(function () {
        Route::get('/super-admin', [SuperAdminController::class, 'index'])->name('central.dashboard');

        // Subscription Plans Management
        Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('central.plans.store');
        Route::put('/plans/{plan}', [SuperAdminController::class, 'updatePlan'])->name('central.plans.update');
        Route::delete('/plans/{plan}', [SuperAdminController::class, 'destroyPlan'])->name('central.plans.destroy');

        // Tenant Health & Lifecycle Management
        Route::put('/tenants/{tenant}', [SuperAdminController::class, 'updateTenant'])->name('central.tenants.update');
        Route::post('/tenants/{tenant}/toggle-status', [SuperAdminController::class, 'toggleTenantStatus'])->name('central.tenants.toggle');
        Route::post('/tenants/{tenant}/backup', [SuperAdminController::class, 'backupTenant'])->name('central.tenants.backup');
        Route::post('/tenants/{tenant}/sync', [SuperAdminController::class, 'syncTenantDatabase'])->name('central.tenants.sync');
        Route::get('/tenants/{tenant}/impersonate', [SuperAdminController::class, 'impersonate'])->name('central.tenants.impersonate');
        Route::post('/tenants/{tenant}/domain', [SuperAdminController::class, 'addDomain'])->name('central.tenants.domain');
        Route::post('/tenants/{tenant}/offboard', [SuperAdminController::class, 'offboard'])->name('central.tenants.offboard');
        
        // Inquiries Management
        Route::post('/super-admin/inquiries/{inquiry}/status', [SuperAdminController::class, 'updateInquiryStatus'])->name('central.inquiries.status');
        Route::delete('/super-admin/inquiries/{inquiry}', [SuperAdminController::class, 'destroyInquiry'])->name('central.inquiries.destroy');

        // Profile & Central Admin Users Management
        Route::post('/super-admin/profile', [SuperAdminController::class, 'updateProfile'])->name('central.profile.update');
        Route::post('/super-admin/users', [SuperAdminController::class, 'storeAdminUser'])->name('central.users.store');
        Route::delete('/super-admin/users/{user}', [SuperAdminController::class, 'destroyAdminUser'])->name('central.users.destroy');

        Route::post('/super-admin/settings', [SuperAdminController::class, 'updateSettings'])->name('central.settings.update');
        Route::post('/super-admin/coupons', [SuperAdminController::class, 'storeCoupon'])->name('central.coupons.store');
        Route::delete('/super-admin/coupons/{id}', [SuperAdminController::class, 'destroyCoupon'])->name('central.coupons.destroy');

        Route::post('/super-admin/approvals/{id}/approve', [SuperAdminController::class, 'approvePayment'])->name('central.approvals.approve');
        Route::post('/super-admin/approvals/{id}/reject', [SuperAdminController::class, 'rejectPayment'])->name('central.approvals.reject');
    });
});

