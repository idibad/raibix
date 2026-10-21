<?php

namespace App\Http\Controllers\Tenant\ERP;

use App\Http\Controllers\Controller;
use App\Models\AcademicEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    /**
     * Display Academic Calendar.
     */
    public function index()
    {
        $events = AcademicEvent::all();
        return Inertia::render('Tenant/Admin/ERP/Calendar/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Store new calendar event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string|in:holiday,exam,ptm,event,other',
            'is_school_off' => 'boolean',
        ]);

        $event = AcademicEvent::create($validated);

        if ($validated['type'] === 'ptm') {
            \App\Models\Notice::create([
                'title' => 'Scheduled PTM: ' . $validated['title'],
                'content' => $validated['description'] ?? 'Parent-Teacher Meeting (PTM) has been scheduled.',
                'target_audience' => 'parents',
                'category' => 'PTM Schedule',
                'publish_date' => $validated['start_date'],
                'created_by' => auth()->id(),
                'send_sms_alert' => true,
                'send_whatsapp_alert' => true,
            ]);
        }

        return redirect()->back()->with('success', 'Academic event created successfully.');
    }

    /**
     * Delete calendar event.
     */
    public function destroy(int $id)
    {
        $event = AcademicEvent::findOrFail($id);
        $event->delete();

        return redirect()->back()->with('success', 'Academic event deleted successfully.');
    }
}
