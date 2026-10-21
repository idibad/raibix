<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CentralAuthController extends Controller
{
    /**
     * Display the Central Super Admin Login view.
     */
    public function showLoginForm(): Response
    {
        return Inertia::render('Central/Login');
    }

    /**
     * Handle authentication attempt for Central Super Admin.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/super-admin');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our Central Super Admin records.',
        ]);
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
