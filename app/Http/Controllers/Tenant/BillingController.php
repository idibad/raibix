<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * Display the Billing & Subscription overview.
     */
    public function index(): Response
    {
        $tenant = tenant();
        $tenantData = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '[]', true) ?? []);

        // Retrieve Central Data
        $centralData = tenancy()->central(function() use ($tenant) {
            $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get();
            $transactions = \App\Models\SaaSTransaction::where('tenant_id', $tenant->id)->latest()->get();
            $referrals = \App\Models\Referral::where('referrer_tenant_id', $tenant->id)->with('referred:id,name')->get();
            
            return compact('plans', 'transactions', 'referrals');
        });

        $currentPlanId = $tenantData['plan_id'] ?? null;
        $currentPlan = $centralData['plans']->firstWhere('id', $currentPlanId) ?? $centralData['plans']->first();

        return Inertia::render('Tenant/Admin/Billing', [
            'currentPlan' => $currentPlan,
            'plans' => $centralData['plans'],
            'transactions' => $centralData['transactions'],
            'referrals' => $centralData['referrals'],
            'referralCode' => $tenantData['referral_code'] ?? null,
            'tenant' => [
                'id' => $tenant->id,
                'status' => $tenant->status ?? $tenantData['status'] ?? 'active',
                'created_at' => $tenant->created_at,
            ],
            'trialEndsAt' => $tenant->trial_ends_at,
        ]);
    }

    /**
     * Generate a unique referral code.
     */
    public function generateReferral(Request $request)
    {
        $tenant = tenant();
        $data = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '[]', true) ?? []);
        
        if (empty($data['referral_code'])) {
            $code = 'REF-' . strtoupper(Str::random(6));
            $data['referral_code'] = $code;
            
            $tenant->update(['data' => $data]);
        }

        return redirect()->back()->with('success', 'Referral code generated successfully!');
    }

    /**
     * Redirect to the central checkout flow to upgrade.
     */
    public function upgrade(Request $request)
    {
        $request->validate(['plan_code' => 'required|string']);
        
        $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
        
        // Use external redirection back to central
        $url = "http://{$centralDomain}/checkout/{$request->plan_code}?upgrade_tenant=" . tenant()->id;
        
        return Inertia::location($url);
    }
}
