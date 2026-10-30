<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantHomeController extends Controller
{
    /**
     * Display the public tenant landing page.
     * Shows school info, available classes for admission, and quick stats.
     */
    public function index(): Response
    {
        $tenant = tenancy()->tenant;

        // Resolve tenant data from the tenancy data bag
        $data = is_array($tenant?->data)
            ? $tenant->data
            : json_decode($tenant?->data ?? '{}', true) ?? [];

        // Build school profile from tenant attributes + data bag
        $school = [
            'id'          => $tenant?->id ?? 'school',
            'name'        => $tenant?->name ?? config('app.name', 'School Portal'),
            'tagline'     => $data['tagline'] ?? 'Empowering Minds, Shaping Futures',
            'address'     => $tenant?->address ?? ($data['address'] ?? null),
            'phone'       => $tenant?->phone ?? ($data['phone'] ?? ($tenant?->admin_phone ?? ($data['admin_phone'] ?? null))),
            'email'       => $tenant?->email ?? ($data['email'] ?? ($tenant?->admin_email ?? ($data['admin_email'] ?? null))),
            'logo'                 => $tenant?->logo ?? null,
            'logo_bg'              => $tenant?->logo_bg ?? ($data['logo_bg'] ?? 'dark'),
            'cover_color'          => $tenant?->cover_color ?? ($data['cover_color'] ?? '#1A73E8'),
            'secondary_color'      => $tenant?->secondary_color ?? ($data['secondary_color'] ?? '#0F172A'),
            'accent_color'         => $tenant?->accent_color ?? ($data['accent_color'] ?? '#3B82F6'),
            'established'          => $data['established'] ?? null,
            'type'                 => $data['school_type'] ?? 'School',
            'hero_heading'         => $tenant?->hero_heading ?? ($data['hero_heading'] ?? null),
            'hero_description'     => $tenant?->hero_description ?? ($data['hero_description'] ?? null),
            'about_vision'         => $tenant?->about_vision ?? ($data['about_vision'] ?? null),
            'academics_description' => $tenant?->academics_description ?? ($data['academics_description'] ?? null),
        ];

        // Available classes for admission inquiry
        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);

        // Public stats — no sensitive data
        $stats = [
            'students' => Student::where('status', 'active')->count(),
            'staff'    => User::whereIn('user_type', ['staff', 'teacher'])->count(),
            'classes'  => $classes->count(),
        ];

        return Inertia::render('Tenant/Landing', [
            'school'  => $school,
            'classes' => $classes,
            'stats'   => $stats,
        ]);
    }
}
