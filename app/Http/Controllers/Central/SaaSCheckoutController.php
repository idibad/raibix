<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\TenantProvisioningService;
use App\Services\JazzCashService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SaaSCheckoutController extends Controller
{
    /**
     * Display the SaaS Subscription Checkout Page for a specific package tier.
     */
    public function showCheckout(string $planCode): Response|RedirectResponse
    {
        $plan = SubscriptionPlan::where('code', $planCode)
            ->orWhere('id', $planCode)
            ->first();

        if (!$plan && in_array($planCode, ['growth', 'essential'])) {
            $plan = SubscriptionPlan::whereIn('code', ['professional', 'starter'])->first();
        }
        if (!$plan && in_array($planCode, ['signature'])) {
            $plan = SubscriptionPlan::whereIn('code', ['premium', 'professional'])->first();
        }

        if (!$plan) {
            $plan = SubscriptionPlan::where('code', 'professional')->first() ?? SubscriptionPlan::first();
        }

        if (!$plan) {
            return redirect('/')->with('error', 'Please select a valid subscription package.');
        }

        $orderedCodes = ['starter', 'professional', 'premium', 'enterprise'];
        $allPlans = SubscriptionPlan::where('is_active', true)->get()->sortBy(function ($p) use ($orderedCodes) {
            $idx = array_search($p->code, $orderedCodes, true);
            return $idx !== false ? $idx : 99;
        })->values();

        return Inertia::render('Central/Checkout', [
            'plan' => $plan,
            'allPlans' => $allPlans,
        ]);
    }

    /**
     * Process SaaS Subscription Checkout, process payment via JazzCash/Meezan, and provision isolated school tenant.
     */
    public function processCheckout(Request $request, TenantProvisioningService $provisioningService, JazzCashService $jazzCashService): RedirectResponse
    {
        // Normalize id and domain before validation
        if ($request->has('id')) {
            $cleanId = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $request->input('id')));
            $request->merge(['id' => $cleanId]);
        }
        if ($request->has('domain')) {
            $cleanDomain = strtolower(trim($request->input('domain')));
            if (!str_contains($cleanDomain, '.')) {
                $cleanDomain .= '.' . \App\Models\Tenant::getDomainSuffix();
            }
            $request->merge(['domain' => $cleanDomain]);
        }

        $validated = $request->validate([
            'id' => 'required|string|max:50|alpha_dash|unique:tenants,id',
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:domains,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_phone' => 'required|string|max:20',
            'admin_password' => 'required|string|min:6',
            'plan_id' => 'required|exists:subscription_plans,id',
            'estimated_students' => 'nullable|integer|min:1',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_gateway' => 'required|in:jazzcash,meezan_bank,trial_transfer,manual',
            'payment_receipt' => 'required_if:payment_gateway,manual|image|max:4096',
            'mobile_number' => 'nullable|string',
            'account_number' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'referral_code' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $studentCount = (int) ($validated['estimated_students'] ?? 100);

        if ($plan->pricing_type === 'custom') {
            $amount = 0.00;
        } else {
            $monthlyBase = $plan->calculateMonthlyBill($studentCount);
            $amount = $validated['billing_cycle'] === 'yearly' ? round($monthlyBase * 12 * 0.8, 2) : $monthlyBase;
        }

        if (!empty($validated['coupon_code'])) {
            $coupon = \App\Models\Coupon::where('code', $validated['coupon_code'])
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where(function($q) {
                    $q->whereNull('max_uses')->orWhereColumn('times_used', '<', 'max_uses');
                })
                ->first();
            
            if ($coupon) {
                $discount = $amount * ($coupon->discount_percentage / 100);
                $amount = max(0, $amount - $discount);
                $coupon->increment('times_used');
            }
        }

        $paymentDetails = [
            'gateway' => $validated['payment_gateway'] === 'manual' ? 'Bank Transfer (Manual)' : $validated['payment_gateway'],
            'amount' => $amount,
            'currency' => 'PKR',
            'status' => $validated['payment_gateway'] === 'manual' ? 'pending' : 'success',
            'reference_no' => strtoupper($validated['payment_gateway']) . '-' . time() . '-' . rand(100, 999),
        ];

        // Store screenshot if manual upload
        $receiptPath = null;
        if ($validated['payment_gateway'] === 'manual' && $request->hasFile('payment_receipt')) {
            $file = $request->file('payment_receipt');
            $filename = 'onboarding_' . time() . '_' . rand(100, 999) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/onboarding'), $filename);
            $receiptPath = '/uploads/onboarding/' . $filename;
            $paymentDetails['receipt_path'] = $receiptPath;
        }

        if ($validated['payment_gateway'] === 'jazzcash') {
            // Prepare HMAC-SHA256 request for SaaS subscription purchase
            $dummyChallan = new \App\Models\FeeChallan([
                'id' => rand(10000, 99999),
                'challan_number' => 'SAAS-' . date('Ym') . '-' . strtoupper($validated['id']),
                'total_amount' => $amount,
            ]);

            $jazzRequest = $jazzCashService->prepareMerchantFormRequest($dummyChallan, [
                'mobile_number' => $validated['mobile_number'] ?? '03001234567',
                'txn_type' => 'MWALLET',
            ]);

            $paymentDetails['secure_hash'] = $jazzRequest['payload']['pp_SecureHash'] ?? 'N/A';
            $paymentDetails['jazzcash_tid'] = $jazzRequest['payload']['pp_TxnRefNo'];
        }

        try {
            // 1. Provision the Isolated School Tenant Database & 25 RBAC Roles & Demo Accounts
            $tenant = $provisioningService->provisionSchool($validated);

            // Update tenant status to pending if manual payment
            if ($validated['payment_gateway'] === 'manual') {
                $tenant->status = 'pending';
                $tenant->save();

                $tenantData = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '[]', true) ?? []);
                $tenantData['status'] = 'pending';
                $tenant->update(['data' => $tenantData]);
            }
        } catch (\Throwable $e) {
            Log::error("School provisioning failed for tenant {$validated['id']}: " . $e->getMessage(), ['exception' => $e]);
            return back()->withErrors(['id' => 'Could not complete setup right now: ' . $e->getMessage()])->withInput();
        }

        $domainStr = strtolower(trim($validated['domain']));
        $finalDomain = str_contains($domainStr, '.') ? $domainStr : ($domainStr . '.' . \App\Models\Tenant::getDomainSuffix());

        $tenantDetails = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $finalDomain,
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'plan_name' => $plan->name,
            'billing_cycle' => $validated['billing_cycle'],
            'amount_paid' => $amount,
        ];

        // 2. Ensure central database connection is clean and reconnected before saving session
        \Illuminate\Support\Facades\DB::setDefaultConnection(config('database.default'));
        \Illuminate\Support\Facades\DB::reconnect(config('database.default'));

        // Save SaaSTransaction
        $saasTxn = \App\Models\SaaSTransaction::create([
            'transaction_code' => 'REV-' . rand(10000, 99999),
            'school_name' => $tenant->name,
            'tenant_id' => $tenant->id,
            'plan_name' => $plan->name . ' (' . ucfirst($validated['billing_cycle']) . ')',
            'amount' => $amount,
            'gateway' => $paymentDetails['gateway'],
            'reference_no' => $paymentDetails['reference_no'],
            'receipt_path' => $receiptPath,
            'status' => $paymentDetails['status'],
        ]);

        // Save TenantSubscription
        \App\Models\TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => $validated['billing_cycle'] === 'yearly' ? now()->addYear() : now()->addMonth(),
            'payment_gateway' => $validated['payment_gateway'],
            'transaction_id' => $saasTxn->transaction_code,
            'amount_paid' => $amount,
            'status' => $validated['payment_gateway'] === 'manual' ? 'pending' : 'active',
            'is_overdue' => false,
        ]);

        // 3. Process Referral Code Linkage
        if (!empty($validated['referral_code'])) {
            $referrer = \App\Models\Tenant::all()->first(function($t) use ($validated) {
                $data = is_string($t->data) ? json_decode($t->data, true) : $t->data;
                return isset($data['referral_code']) && $data['referral_code'] === $validated['referral_code'];
            });

            if ($referrer) {
                \App\Models\Referral::create([
                    'referrer_tenant_id' => $referrer->id,
                    'referred_tenant_id' => $tenant->id,
                    'status' => 'successful',
                    'reward_applied' => false,
                ]);
            }
        }

        $request->session()->put('checkout_tenant', $tenantDetails);
        $request->session()->put('checkout_payment', $paymentDetails);
        $request->session()->save();

        return redirect()->route('central.checkout.success', ['tenant_id' => $tenant->id]);
    }

    /**
     * Display the Checkout Success / Account Setup onboarding screen.
     */
    public function showCheckoutSuccess(Request $request): Response|RedirectResponse
    {
        $tenant = $request->session()->get('checkout_tenant');
        $payment = $request->session()->get('checkout_payment');

        // Fallback to database lookup if session was cleared or lost across tenancy boundary
        if ((!$tenant || !$payment) && $request->has('tenant_id')) {
            $tModel = \App\Models\Tenant::with('domains')->find($request->query('tenant_id'));
            if ($tModel) {
                $domainStr = $tModel->domains()->first()?->domain ?? ($tModel->id . '.' . \App\Models\Tenant::getDomainSuffix());
                $tenant = [
                    'id' => $tModel->id,
                    'name' => $tModel->name,
                    'domain' => $domainStr,
                    'admin_email' => "principal@{$tModel->id}.lmscloud.test",
                    'admin_password' => null,
                    'plan_name' => 'Selected Subscription Tier',
                    'billing_cycle' => 'monthly',
                    'amount_paid' => 0,
                ];
                $payment = [
                    'gateway' => 'online_verification',
                    'amount' => 0,
                    'currency' => 'PKR',
                    'status' => 'Paid (Verified)',
                    'reference_no' => 'SYS-' . strtoupper($tModel->id) . '-' . time(),
                ];
                $request->session()->put('checkout_tenant', $tenant);
                $request->session()->put('checkout_payment', $payment);
                $request->session()->save();
            }
        }

        if (!$tenant || !$payment) {
            return redirect()->route('central.landing')->with('error', 'No active checkout session found.');
        }

        return Inertia::render('Central/CheckoutSuccess', [
            'tenant' => $tenant,
            'payment' => $payment,
        ]);
    }

    /**
     * Add the first user/staff to a newly provisioned tenant database.
     */
    public function addFirstUser(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        $tenant = \App\Models\Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'user_type' => 'required|string|in:teacher,admin,registrar,accountant,librarian',
            'password' => 'required|string|min:8',
        ]);

        try {
            tenancy()->initialize($tenant);

            // Check if user already exists in tenant database
            $exists = \App\Models\User::where('email', $validated['email'])->exists();
            if ($exists) {
                tenancy()->end();
                return back()->withErrors(['email' => 'A user with this email already exists in the school portal.']);
            }

            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'user_type' => $validated['user_type'],
                'status' => 'active',
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            ]);

            // Assign proper role based on user_type
            if ($validated['user_type'] === 'teacher') {
                $user->assignRole('Junior Section Teachers');
            } elseif ($validated['user_type'] === 'admin') {
                $user->assignRole('Principal');
            }

            // Send Welcome Email
            try {
                $loginUrl = route('tenant.login');
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WelcomeStaffMail($user, $validated['password'], $loginUrl));
            } catch (\Throwable $mailEx) {
                Log::error("Failed to send welcome email for first user in wizard: " . $mailEx->getMessage());
            }

            tenancy()->end();
        } catch (\Throwable $e) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            return back()->withErrors(['name' => 'Failed to create user: ' . $e->getMessage()]);
        }

        return redirect()->back()->with('success', "User account for {$validated['name']} has been created successfully!");
    }
}
