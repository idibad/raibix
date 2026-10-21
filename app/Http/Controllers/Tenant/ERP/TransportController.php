<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\TransportVehicle;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportAllocation;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TransportController extends Controller
{
    /**
     * Display Transport fleet, routes, and allocations.
     */
    public function index()
    {
        $vehicles = TransportVehicle::all();
        $routes = TransportRoute::with('vehicle', 'stops')->get();
        $allocations = TransportAllocation::with('route', 'vehicle', 'user')->get();
        
        $students = Student::with('user')->where('status', 'active')->get();
        $staff = User::whereIn('user_type', ['teacher', 'admin', 'staff'])->get();

        return Inertia::render('Tenant/Admin/ERP/Transport/Index', [
            'vehicles' => $vehicles,
            'routes' => $routes,
            'allocations' => $allocations,
            'students' => $students,
            'staff' => $staff,
        ]);
    }

    /**
     * Store new vehicle.
     */
    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'vehicle_number' => 'required|string|max:100|unique:transport_vehicles,vehicle_number',
            'model_name' => 'nullable|string|max:100',
            'capacity' => 'required|integer|min:1',
            'driver_name' => 'required|string|max:255',
            'driver_phone' => 'required|string|max:50',
        ]);

        TransportVehicle::create($validated);

        return redirect()->back()->with('success', 'Vehicle registered successfully.');
    }

    /**
     * Store new route.
     */
    public function storeRoute(Request $request)
    {
        $validated = $request->validate([
            'route_name' => 'required|string|max:255',
            'vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'pickup_time' => 'nullable|string',
            'drop_time' => 'nullable|string',
            'monthly_fee' => 'required|numeric|min:0',
        ]);

        TransportRoute::create($validated);

        return redirect()->back()->with('success', 'Route registered successfully.');
    }

    /**
     * Store route stop.
     */
    public function storeStop(Request $request)
    {
        $validated = $request->validate([
            'route_id' => 'required|exists:transport_routes,id',
            'stop_name' => 'required|string|max:255',
            'pickup_time' => 'nullable|string',
        ]);

        TransportStop::create($validated);

        return redirect()->back()->with('success', 'Route stop added.');
    }

    /**
     * Assign student/staff to transport route & vehicle.
     */
    public function allocateTransport(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_type' => 'required|in:student,staff',
            'route_id' => 'required|exists:transport_routes,id',
            'vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'pickup_point' => 'required|string|max:255',
        ]);

        TransportAllocation::create([
            'user_id' => $validated['user_id'],
            'user_type' => $validated['user_type'],
            'route_id' => $validated['route_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'pickup_point' => $validated['pickup_point'],
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', 'Transport allocated successfully.');
    }
}
