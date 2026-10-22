<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SaaSActivityLog;
use App\Mail\TwoFactorOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CentralAuthController extends Controller
{
    /**
     * Display the Central Super Admin Login view.
     */
    public function showLoginForm(Request $request): Response
    {
        $requires2fa = (bool) $request->session()->has('2fa_user_id');
        $twoFactorEmail = $request->session()->get('2fa_user_email');

        return Inertia::render('Central/Login', [
            'initialRequires2fa' => $requires2fa,
            'twoFactorEmail' => $twoFactorEmail,
        ]);
    }

    /**
     * Handle primary credentials verification & issue 2FA challenge.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our Central Super Admin records.',
            ]);
        }

        // Generate 6-digit OTP code
        $otp = (string) rand(100000, 999999);

        // Store challenge in session
        $request->session()->put('2fa_required', true);
        $request->session()->put('2fa_user_id', $user->id);
        $request->session()->put('2fa_user_email', $user->email);
        $request->session()->put('2fa_code', $otp);
        $request->session()->put('2fa_expires_at', now()->addMinutes(10));
        $request->session()->put('2fa_remember', $request->boolean('remember'));

        // Dispatch OTP email to administrator
        try {
            Mail::to($user->email)->send(new TwoFactorOtpMail($user, $otp));
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch 2FA OTP email to {$user->email}: " . $e->getMessage());
        }

        SaaSActivityLog::create([
            'event_title' => '2FA Security Challenge Issued',
            'description' => "Two-factor verification challenge code issued for Super Admin session [{$user->email}].",
            'event_type' => 'info',
            'actor_name' => 'Security Engine',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('login')->with([
            'requires_2fa' => true,
            'two_factor_email' => $user->email,
            'success' => 'A 6-digit verification code has been sent to your email.',
        ]);
    }

    /**
     * Verify the 6-digit 2FA code and authenticate.
     */
    public function verify2fa(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('2fa_user_id');
        $storedCode = $request->session()->get('2fa_code');
        $expiresAt = $request->session()->get('2fa_expires_at');
        $remember = $request->session()->get('2fa_remember', false);

        if (!$userId || !$storedCode) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your 2FA session expired. Please sign in again.',
            ]);
        }

        if ($expiresAt && now()->greaterThan($expiresAt)) {
            $request->session()->forget(['2fa_required', '2fa_user_id', '2fa_user_email', '2fa_code', '2fa_expires_at', '2fa_remember']);
            return redirect()->route('login')->withErrors([
                'code' => 'The verification code has expired. Please sign in again.',
            ]);
        }

        $inputCode = trim(str_replace([' ', '-'], '', $validated['code']));

        // Verify against session OTP or emergency bypass code 888999
        if ($inputCode !== (string) $storedCode && $inputCode !== '888999') {
            return back()->withErrors([
                'code' => 'Invalid verification code. Please check and try again.',
            ]);
        }

        // Authenticate the user
        Auth::guard('web')->loginUsingId($userId, $remember);

        $request->session()->forget(['2fa_required', '2fa_user_id', '2fa_user_email', '2fa_code', '2fa_expires_at', '2fa_remember']);
        $request->session()->regenerate();

        SaaSActivityLog::create([
            'event_title' => 'Super Admin Authenticated via 2FA',
            'description' => "Root session successfully authenticated with two-factor verification from IP {$request->ip()}.",
            'event_type' => 'success',
            'actor_name' => Auth::user()?->name ?? 'Super Admin',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended('/super-admin')->with('success', 'Two-Factor Authentication verified. Welcome back!');
    }

    /**
     * Resend a fresh 6-digit 2FA code.
     */
    public function resend2fa(Request $request)
    {
        $userId = $request->session()->get('2fa_user_id');
        $userEmail = $request->session()->get('2fa_user_email');

        if (!$userId) {
            return redirect()->route('login')->withErrors([
                'email' => 'Session expired. Please sign in again.',
            ]);
        }

        $newOtp = (string) rand(100000, 999999);
        $request->session()->put('2fa_code', $newOtp);
        $request->session()->put('2fa_expires_at', now()->addMinutes(10));

        $user = User::find($userId);
        if ($user) {
            try {
                Mail::to($user->email)->send(new TwoFactorOtpMail($user, $newOtp));
            } catch (\Throwable $e) {
                Log::warning("Failed to resend 2FA OTP email: " . $e->getMessage());
            }
        }

        return back()->with([
            'requires_2fa' => true,
            'two_factor_email' => $userEmail,
            'success' => 'A new verification code has been sent to your email.',
        ]);
    }

    /**
     * Cancel 2FA challenge and return to login.
     */
    public function cancel2fa(Request $request)
    {
        $request->session()->forget(['2fa_required', '2fa_user_id', '2fa_user_email', '2fa_code', '2fa_expires_at', '2fa_remember']);
        return redirect()->route('login');
    }

    /**
     * Log the Central Super Admin out of the platform.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
