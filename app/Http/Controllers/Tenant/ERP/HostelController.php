<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelAllocation;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class HostelController extends Controller
{
    /**
     * Display Hostels, Rooms, and occupants allocations.
     */
    public function index()
    {
        $hostels = Hostel::with('rooms')->get();
        $rooms = HostelRoom::with('hostel')->get();
        $allocations = HostelAllocation::with('room.hostel', 'student.user')->get();
        $students = Student::with('user')->where('status', 'active')->get();

        return Inertia::render('Tenant/Admin/ERP/Hostels/Index', [
            'hostels' => $hostels,
            'rooms' => $rooms,
            'allocations' => $allocations,
            'students' => $students,
        ]);
    }

    /**
     * Store new hostel.
     */
    public function storeHostel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:boys,girls,co-ed',
            'address' => 'nullable|string',
            'warden_name' => 'nullable|string|max:255',
            'warden_phone' => 'nullable|string|max:50',
        ]);

        Hostel::create($validated);

        return redirect()->back()->with('success', 'Hostel registered successfully.');
    }

    /**
     * Store new hostel room.
     */
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'hostel_id' => 'required|exists:hostels,id',
            'room_number' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'rent_amount' => 'required|numeric|min:0',
        ]);

        HostelRoom::create($validated);

        return redirect()->back()->with('success', 'Hostel room added successfully.');
    }

    /**
     * Allocate room to student.
     */
    public function allocateRoom(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'room_id' => 'required|exists:hostel_rooms,id',
            'allocation_date' => 'required|date',
        ]);

        $room = HostelRoom::with('allocations')->findOrFail($validated['room_id']);
        $activeOccupants = $room->allocations->where('status', 'active')->count();

        if ($activeOccupants >= $room->capacity) {
            return redirect()->back()->with('error', 'This room is already at full capacity.');
        }

        HostelAllocation::create([
            'student_id' => $validated['student_id'],
            'room_id' => $validated['room_id'],
            'allocation_date' => $validated['allocation_date'],
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', 'Room allocated to student successfully.');
    }

    /**
     * Vacate allocated room.
     */
    public function vacateRoom(Request $request, int $id)
    {
        $validated = $request->validate([
            'vacate_date' => 'required|date',
        ]);

        $allocation = HostelAllocation::findOrFail($id);
        $allocation->update([
            'vacate_date' => $validated['vacate_date'],
            'status' => 'vacated',
        ]);

        return redirect()->back()->with('success', 'Student vacated room successfully.');
    }
}
