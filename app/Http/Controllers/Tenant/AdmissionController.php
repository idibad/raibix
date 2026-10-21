<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\AdmissionApplication;
use App\Models\SchoolClass;
use App\Models\FeeChallan;
use App\Services\AdmissionService;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionController extends Controller
{
    /**
     * Display Admissions Dashboard with Inquiries, Applications, and Class capacities.
     */
    public function index(): Response
    {
        $inquiries = AdmissionInquiry::with('desiredClass')->latest()->get();
        $applications = AdmissionApplication::with('schoolClass')->latest()->get();
        $classes = SchoolClass::all();
        $challans = FeeChallan::where('fee_type', 'admission')->with('student')->latest()->get();

        return Inertia::render('Tenant/Admissions/Index', [
            'inquiries' => $inquiries,
            'applications' => $applications,
            'classes' => $classes,
            'challans' => $challans,
        ]);
    }

    /**
     * Store new Admission Inquiry.
     */
    public function storeInquiry(Request $request, AdmissionService $admissionService)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'previous_school' => 'nullable|string|max:255',
            'desired_class_id' => 'required|exists:classes,id',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $inquiry = $admissionService->createInquiry($validated);

        return redirect()->back()->with('success', "Inquiry [{$inquiry->inquiry_code}] registered! Automated follow-up reminder scheduled.");
    }

    /**
     * Store new Admission Application with documents.
     */
    public function storeApplication(Request $request, AdmissionService $admissionService)
    {
        $validated = $request->validate([
            'inquiry_id' => 'nullable|exists:admission_inquiries,id',
            'student_name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'father_name' => 'required|string|max:255',
            'father_cnic' => 'nullable|string|max:20',
            'father_phone' => 'required|string|max:20',
            'mother_name' => 'nullable|string|max:255',
            'mother_cnic' => 'nullable|string|max:20',
            'test_date' => 'nullable|date',
        ]);

        $application = $admissionService->createApplication($validated);

        return redirect()->back()->with('success', "Application [{$application->application_code}] submitted successfully for document & test evaluation!");
    }

    /**
     * Evaluate test marks and compute Merit Decision.
     */
    public function evaluateMerit(int $applicationId, Request $request, AdmissionService $admissionService)
    {
        $validated = $request->validate([
            'marks_obtained' => 'required|numeric|min:0',
            'total_marks' => 'required|numeric|min:1',
        ]);

        $application = AdmissionApplication::findOrFail($applicationId);
        $status = $admissionService->decideMerit($application, $validated['marks_obtained'], $validated['total_marks']);

        return redirect()->back()->with('success', "Merit decided: Application is now [{$status}]. " . ($status === 'approved' ? 'Admission Fee Challan generated!' : ''));
    }

    /**
     * Finalize Student Enrollment upon fee payment.
     */
    public function enroll(int $applicationId, AdmissionService $admissionService)
    {
        $application = AdmissionApplication::findOrFail($applicationId);

        if ($application->admission_status === 'enrolled') {
            return redirect()->back()->with('error', "This admission application [{$application->application_code}] is already enrolled.");
        }

        $student = $admissionService->enrollStudent($application);

        return redirect()->back()->with('success', "Student enrolled successfully with Roll # [{$student->roll_number}]! Welcome credentials sent to parent and student.");
    }

    /**
     * Verify payment manually and trigger student enrollment.
     */
    public function payChallanManual(int $challanId, FeeService $feeService)
    {
        $challan = FeeChallan::findOrFail($challanId);
        
        $feeService->processPayment($challan, 'cash', $challan->total_amount, [
            'reference_no' => 'MANUAL-' . strtoupper(Str::random(8)),
            'tid' => 'MANUAL-' . strtoupper(Str::random(8))
        ]);

        return redirect()->back()->with('success', 'Fee payment verified manually and student enrolled successfully!');
    }
}
