<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
