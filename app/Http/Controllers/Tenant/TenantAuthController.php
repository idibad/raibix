<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantAuthController extends Controller
{
    /**
     * Show unified tenant multi-portal login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }

        $tenant = tenancy()->tenant;
        $domain = $tenant?->domains()->first()?->domain ?? ('school.' . \App\Models\Tenant::getDomainSuffix());

        return Inertia::render('Tenant/Auth/Login', [
            'tenant' => [
                'id' => $tenant?->id ?? 'school',
                'name' => $tenant?->name ?? config('app.name', 'School Portal'),
                'domain' => $domain,
                'logo' => $tenant?->logo ?? null,
            ],
        ]);
    }

    /**
     * Handle multi-portal authentication and redirect based on user role/type.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $tenantId = function_exists('tenant') && tenant() ? tenant('id') : null;
            $request->session()->put('auth_context', 'tenant');
            $request->session()->put('is_super_admin', false);
            if ($tenantId) {
                $request->session()->put('_tenant_id', $tenantId);
            }

            return $this->redirectToDashboard(Auth::user());
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records for this school.',
        ]);
    }

    private function redirectToDashboard($user)
    {
        if (in_array($user->user_type, ['admin', 'principal', 'accountant', 'registrar', 'librarian', 'vice_principal'])) {
            return redirect()->route('tenant.admin.dashboard');
        }

        if (in_array($user->user_type, ['staff', 'teacher'])) {
            return redirect()->route('tenant.teacher.dashboard');
        }

        if ($user->user_type === 'student') {
            return redirect()->route('tenant.student.dashboard');
        }

        if ($user->user_type === 'parent') {
            return redirect()->route('tenant.parent.dashboard');
        }

        return redirect('/');
    }

    /**
     * Log out user and redirect to login page.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget(['auth_context', 'is_super_admin', '_tenant_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }

    /**
     * Show school portal forgot password form.
     */
    public function showForgotPasswordForm(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }

        $tenant = tenancy()->tenant;
        $domain = $tenant?->domains()->first()?->domain ?? ('school.' . \App\Models\Tenant::getDomainSuffix());

        return Inertia::render('Tenant/Auth/ForgotPassword', [
            'tenant' => [
                'id' => $tenant?->id ?? 'school',
                'name' => $tenant?->name ?? config('app.name', 'School Portal'),
                'domain' => $domain,
                'logo' => $tenant?->logo ?? null,
            ],
            'status' => session('status'),
        ]);
    }

    /**
     * Handle forgot password reset link dispatch.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Prevent user enumeration: provide constant message regardless of user existence
        if ($user) {
            $rawToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($rawToken),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('tenant.password.reset', ['token' => $rawToken, 'email' => $email]);

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
            } catch (\Throwable $e) {
                Log::error("Failed to send password reset email to {$email}: " . $e->getMessage());
            }
        }

        return back()->with('status', 'If an account exists with this email address, a password reset link has been dispatched to your inbox.');
    }

    /**
     * Show reset password form with token.
     */
    public function showResetPasswordForm(Request $request, string $token): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }

        $tenant = tenancy()->tenant;
        $domain = $tenant?->domains()->first()?->domain ?? ('school.' . \App\Models\Tenant::getDomainSuffix());

        return Inertia::render('Tenant/Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
            'tenant' => [
                'id' => $tenant?->id ?? 'school',
                'name' => $tenant?->name ?? config('app.name', 'School Portal'),
                'domain' => $domain,
                'logo' => $tenant?->logo ?? null,
            ],
        ]);
    }

    /**
     * Process password reset and update credentials.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim($request->email));
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Invalid or expired password reset request.']);
        }

        // Validate expiration (60 minutes TTL)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withErrors(['email' => 'Password reset token has expired. Please request a new link.']);
        }

        // Validate hashed token match
        if (!Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid password reset token provided.']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No active user found for this email address.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Invalidate token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('tenant.login')->with('success', 'Your password has been reset successfully! Please log in with your new password.');
    }
}
