<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErpSettingsController extends Controller
{
    /**
     * Display ERP Settings panel.
     */
    public function index()
    {
        $tenant = tenancy()->tenant;
        $academicYears = \App\Models\AcademicYear::with('terms')->get();

        return Inertia::render('Tenant/Admin/ERP/Settings/Index', [
            'settings' => [
                'enable_library' => filter_var($tenant->enable_library ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_transport' => filter_var($tenant->enable_transport ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_hostels' => filter_var($tenant->enable_hostels ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_inventory' => filter_var($tenant->enable_inventory ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_hr' => filter_var($tenant->enable_hr ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_finance' => filter_var($tenant->enable_finance ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_calendar' => filter_var($tenant->enable_calendar ?? true, FILTER_VALIDATE_BOOLEAN),
                'enable_certificates' => filter_var($tenant->enable_certificates ?? true, FILTER_VALIDATE_BOOLEAN),
            ],
            'academic_years' => $academicYears
        ]);
    }

    /**
     * Store updated ERP Settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enable_library' => 'required|boolean',
            'enable_transport' => 'required|boolean',
            'enable_hostels' => 'required|boolean',
            'enable_inventory' => 'required|boolean',
            'enable_hr' => 'required|boolean',
            'enable_finance' => 'required|boolean',
            'enable_calendar' => 'required|boolean',
            'enable_certificates' => 'required|boolean',
        ]);

        $tenant = tenancy()->tenant;
        if (!$tenant) {
            return redirect()->back()->with('error', 'No active tenant school identified.');
        }

        foreach ($validated as $key => $value) {
            $tenant->$key = $value;
        }
        $tenant->save();

        return redirect()->back()->with('success', 'ERP Settings updated successfully.');
    }
}
