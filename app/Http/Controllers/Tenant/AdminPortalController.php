<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\FeeChallan;
use App\Models\SchoolClass;
use App\Models\Notice;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\User;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AdmissionInquiry;
use App\Models\AdmissionApplication;

class AdminPortalController extends Controller
{
    /**
     * Display Principal / Admin Portal Dashboard with complete P0/P1/P2 Operational Suite.
     */
    public function index(): Response
    {
        $totalStudents = Student::where('status', 'active')->count();
        $today = date('Y-m-d');
        
        $presentToday = Attendance::where('date', $today)->where('status', 'present')->count();
        $attendanceRate = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 100.0;

        $totalFeeCollection = FeeChallan::where('month_year', date('Y-m'))->sum('paid_amount');
        $outstandingFees = FeeChallan::whereIn('status', ['unpaid', 'overdue'])->sum('total_amount');

        $defaulters = FeeChallan::whereIn('status', ['unpaid', 'overdue'])
            ->with(['student.user', 'student.currentClass', 'student.currentSection'])
            ->latest()
            ->take(10)
            ->get();

        $classes = SchoolClass::with(['sections.classTeacher', 'subjects'])->withCount('students')->get();
        $sections = Section::with(['schoolClass', 'classTeacher'])->get();
        $recentNotices = Notice::latest()->take(10)->get();

        // P0 Staff / User management list
        $users = User::latest()->get();

        // P0 Student management list with status logs & document vault
        $students = Student::with(['user', 'parent', 'currentClass', 'currentSection', 'statusLogs.changedBy', 'documents.uploadedBy'])->latest()->get();

        // P0 Admissions Pipeline & Inquiries
        $inquiries = AdmissionInquiry::with('desiredClass')->latest()->take(25)->get();
        $applications = AdmissionApplication::with('schoolClass')->latest()->take(25)->get();

        // P1 #5 Timetable matrix across sections
        $timetables = \App\Models\Timetable::with(['section.schoolClass', 'subject', 'teacher'])->orderBy('day_of_week')->orderBy('start_time')->get();

        // P1 #6 Fee structures & student overrides
        $feeStructures = \App\Models\FeeStructure::with('schoolClass')->get();
        $feeOverrides = \Illuminate\Support\Facades\DB::table('student_fee_overrides')
            ->join('students', 'student_fee_overrides.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('student_fee_overrides.*', 'users.name as student_name', 'students.roll_number', 'students.admission_number')
            ->get();

        // P1 #7 Staff daily attendance logs & leaves
        $staffAttendanceLogs = \Illuminate\Support\Facades\DB::table('staff_attendances')->where('date', $today)->get()->keyBy('user_id');
        $staffLeaves = \Illuminate\Support\Facades\DB::table('staff_leaves')
            ->join('users', 'staff_leaves.user_id', '=', 'users.id')
            ->select('staff_leaves.*', 'users.name as staff_name', 'users.user_type as role')
            ->orderBy('created_at', 'desc')
            ->take(25)
            ->get();

        // Student leave requests from parent/student portals
        $studentLeaves = Leave::with(['student.user', 'student.currentClass', 'student.currentSection', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // P1 #9 Exams & Subject list
        $exams = \App\Models\Exam::orderBy('start_date', 'desc')->get();
        $subjects = \App\Models\Subject::with('schoolClass')->get();

        // Sports & Extracurricular Activities
        $sportsActivities = \App\Models\SportsActivity::with('coach')->latest()->get();

        // Student Disciplinary Penalties & Fines
        $penalties = \App\Models\StudentPenalty::with(['student.user', 'issuedBy'])->latest()->get();

        // Comprehensive Finance & Accounts Metrics (P0 #5)
        $monthlyTarget = FeeChallan::where('month_year', date('Y-m'))->sum('total_amount');
        $collectionPercentage = $monthlyTarget > 0 ? round(($totalFeeCollection / $monthlyTarget) * 100, 1) : 100.0;
        $totalCollectedYtd = FeeChallan::sum('paid_amount');
        $recentTransactions = FeeChallan::where('status', 'paid')->with(['student.user', 'student.currentClass'])->latest()->take(20)->get();

        // Billing & Subscription (Central DB)
        $tenant = tenancy()->tenant;
        $tenantData = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '[]', true) ?? []);
        
        $centralBillingData = tenancy()->central(function() use ($tenant) {
            return [
                'plans' => \App\Models\SubscriptionPlan::where('is_active', true)->get(),
                'transactions' => \App\Models\SaaSTransaction::where('tenant_id', $tenant->id)->latest()->get(),
                'referrals' => \App\Models\Referral::where('referrer_tenant_id', $tenant->id)->with('referred:id,name')->get(),
            ];
        });

        $currentPlanId = $tenant->plan_id ?? $tenantData['plan_id'] ?? null;
        $currentPlan = collect($centralBillingData['plans'])->firstWhere('id', $currentPlanId) ?? collect($centralBillingData['plans'])->first();

        // Fetch student pending manual fee payments
        $pendingPayments = \App\Models\FeeTransaction::where('status', 'pending')
            ->with(['feeChallan.student.user', 'feeChallan.student.currentClass', 'feeChallan.student.currentSection'])
            ->latest()
            ->get()
            ->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'challan_id' => $txn->fee_challan_id,
                    'challan_number' => $txn->feeChallan?->challan_number,
                    'month_year' => $txn->feeChallan?->month_year,
                    'amount' => (float) $txn->amount,
                    'payment_date' => $txn->payment_date ? $txn->payment_date->format('Y-m-d') : null,
                    'transaction_reference' => $txn->transaction_reference,
                    'receipt_path' => $txn->receipt_path,
                    'status' => $txn->status,
                    'student_name' => $txn->feeChallan?->student?->user?->name,
                    'roll_number' => $txn->feeChallan?->student?->roll_number,
                    'class_name' => $txn->feeChallan?->student?->currentClass?->name,
                    'section_name' => $txn->feeChallan?->student?->currentSection?->name,
                ];
            });

        return Inertia::render('Tenant/Admin/Dashboard', [
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'saas_plans' => $centralBillingData['plans'],
            'saas_transactions' => $centralBillingData['transactions'],
            'saas_referrals' => $centralBillingData['referrals'],
            'referral_code' => $tenantData['referral_code'] ?? null,
            'trial_ends_at' => $tenant->trial_ends_at,
            'current_user' => auth()->user(),
            'current_role' => auth()->user()?->roles?->first()?->name ?? (auth()->user()?->user_type === 'admin' ? 'Principal' : auth()->user()?->user_type),
            'kpis' => [
                'total_students' => $totalStudents,
                'attendance_rate' => $attendanceRate,
                'fee_collected_month' => $totalFeeCollection,
                'outstanding_fees' => $outstandingFees,
            ],
            'finance_stats' => [
                'total_collected_ytd' => $totalCollectedYtd,
                'monthly_collection' => $totalFeeCollection,
                'monthly_target' => $monthlyTarget,
                'collection_percentage' => $collectionPercentage,
                'total_outstanding' => $outstandingFees,
            ],
            'recent_transactions' => $recentTransactions,
            'defaulters' => $defaulters,
            'classes' => $classes,
            'sections' => $sections,
            'subjects' => $subjects,
            'notices' => $recentNotices,
            'users' => $users,
            'students' => $students,
            'inquiries' => $inquiries,
            'applications' => $applications,
            'timetables' => $timetables,
            'fee_structures' => $feeStructures,
            'fee_overrides' => $feeOverrides,
            'staff_attendance_logs' => $staffAttendanceLogs,
            'staff_leaves' => $staffLeaves,
            'student_leaves' => $studentLeaves,
            'exams' => $exams,
            'sports_activities' => $sportsActivities,
            'penalties' => $penalties,
            'academic_years' => \App\Models\AcademicYear::with('terms')->get(),
            'pending_payments' => $pendingPayments,
        ]);
    }

    /**
     * Approve a pending student manual fee payment.
     */
    public function approveFeePayment(Request $request, $id)
    {
        $txn = \App\Models\FeeTransaction::findOrFail($id);
        $txn->status = 'success';
        $txn->save();

        $challan = $txn->feeChallan;
        if ($challan) {
            $challan->paid_amount = $challan->total_amount;
            $challan->status = 'paid';
            $challan->save();
        }

        return redirect()->back()->with('success', 'Student fee payment receipt approved and challan marked as Paid!');
    }

    /**
     * Reject a pending student manual fee payment.
     */
    public function rejectFeePayment(Request $request, $id)
    {
        $txn = \App\Models\FeeTransaction::findOrFail($id);
        $txn->status = 'failed';
        $txn->save();

        return redirect()->back()->with('error', 'Student fee payment receipt was rejected.');
    }

    /**
     * Upload platform renewal slip to central landlord database.
     */
    public function renewSubscription(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required',
            'billing_cycle' => 'required|in:monthly,yearly',
            'transaction_reference' => 'required|string|max:100',
            'payment_receipt' => 'required|image|max:4096',
        ]);

        $tenant = tenant();
        $plan = tenancy()->central(function() use ($validated) {
            return \App\Models\SubscriptionPlan::findOrFail($validated['plan_id']);
        });

        $amount = $validated['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $receiptPath = null;
        if ($request->hasFile('payment_receipt')) {
            $file = $request->file('payment_receipt');
            $filename = 'renewal_' . $tenant->id . '_' . time() . '_' . rand(100, 999) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/renewals'), $filename);
            $receiptPath = '/uploads/renewals/' . $filename;
        }

        // Switch to central DB to create SaaSTransaction and TenantSubscription in pending status
        tenancy()->central(function() use ($tenant, $plan, $amount, $validated, $receiptPath) {
            $saasTxn = \App\Models\SaaSTransaction::create([
                'transaction_code' => 'REV-' . rand(10000, 99999),
                'school_name' => $tenant->name,
                'tenant_id' => $tenant->id,
                'plan_name' => $plan->name . ' (' . ucfirst($validated['billing_cycle']) . ')',
                'amount' => $amount,
                'gateway' => 'Bank Transfer (Renewal)',
                'reference_no' => $validated['transaction_reference'],
                'receipt_path' => $receiptPath,
                'status' => 'pending',
            ]);

            \App\Models\TenantSubscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'starts_at' => now(),
                    'ends_at' => $validated['billing_cycle'] === 'yearly' ? now()->addYear() : now()->addMonth(),
                    'payment_gateway' => 'manual',
                    'transaction_id' => $saasTxn->transaction_code,
                    'amount_paid' => $amount,
                    'status' => 'pending',
                    'is_overdue' => false,
                ]
            );
        });

        return redirect()->back()->with('success', 'Your subscription renewal slip has been uploaded successfully. It will be activated once the platform admin approves it.');
    }

    /**
     * Store new School Class.
     */
    public function storeClass(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'level' => 'required|string|max:50',
            'numeric_grade' => 'nullable|integer',
        ]);

        SchoolClass::create([
            'name' => $validated['name'],
            'level' => $validated['level'],
            'numeric_grade' => $validated['numeric_grade'] ?? 1,
        ]);

        return redirect()->back()->with('success', "Class [{$validated['name']}] added to academic hierarchy!");
    }

    /**
     * Delete School Class.
     */
    public function destroyClass($id)
    {
        SchoolClass::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Class removed cleanly.');
    }

    /**
     * Store new Section for a Class.
     */
    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'class_teacher_id' => 'nullable|exists:users,id',
        ]);

        Section::create($validated);

        return redirect()->back()->with('success', "Section [{$validated['name']}] assigned to class!");
    }

    /**
     * Delete Section.
     */
    public function destroySection($id)
    {
        Section::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Section removed from class hierarchy.');
    }

    /**
     * Store and publish new school Notice / Circular.
     */
    public function storeNotice(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|string',
            'target_class_id' => 'nullable|exists:classes,id',
            'category' => 'nullable|string',
            'publish_date' => 'nullable|date',
        ]);

        Notice::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target_audience' => $validated['target_audience'],
            'target_class_id' => $validated['target_class_id'] ?? null,
            'category' => $validated['category'] ?? 'General Circular',
            'publish_date' => $validated['publish_date'] ?? date('Y-m-d'),
            'created_by' => auth()->id(),
            'send_sms_alert' => $request->boolean('send_sms_alert', true),
            'send_whatsapp_alert' => $request->boolean('send_whatsapp_alert', true),
        ]);

        return redirect()->back()->with('success', "Notice [{$validated['title']}] published and broadcasted across portals!");
    }

    /**
     * Delete Notice.
     */
    public function destroyNotice($id)
    {
        Notice::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Notice removed from board.');
    }

    /**
     * Dispatch SMS/WhatsApp reminder to fee defaulter parent.
     */
    public function sendDefaulterReminder($id)
    {
        $challan = FeeChallan::with('student.user', 'student.parent')->findOrFail($id);
        $phone = $challan->student->parent?->father_phone ?? $challan->student->user?->phone ?? '03001234567';
        $message = "URGENT REMINDER: Fee Challan {$challan->challan_number} of PKR {$challan->total_amount} is OVERDUE for {$challan->student->user?->name}. Please pay immediately via Parent Portal.";

        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->queueSms($phone, $message, 'fee_challan');
        $notificationService->queueWhatsapp($phone, $message, 'fee_challan', 'fee_challan_overdue');

        return redirect()->back()->with('success', "Overdue reminder SMS/WhatsApp queued for background dispatch to {$phone}!");
    }

    /**
     * Waive late penalty or apply discount to fee challan.
     */
    public function waiveDefaulterLateFee($id, Request $request)
    {
        $challan = FeeChallan::findOrFail($id);
        $discount = floatval($request->discount ?? 500);
        $challan->total_amount = max(0, $challan->total_amount - $discount);
        if ($challan->total_amount <= $challan->paid_amount) {
            $challan->status = 'paid';
        }
        $challan->save();

        return redirect()->back()->with('success', "PKR {$discount} late fee/penalty waived for Challan {$challan->challan_number}!");
    }

    /**
     * Upload and apply custom School Logo for current tenant.
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo'            => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'cover_color'     => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'accent_color'    => 'nullable|string|max:20',
        ]);

        $tenant = tenancy()->tenant;
        if (!$tenant) {
            return redirect()->back()->with('error', 'No active tenant school identified.');
        }

        if ($request->hasFile('logo')) {
            $file      = $request->file('logo');
            $filename  = 'tenant_' . $tenant->id . '_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $destDir   = public_path('uploads/logos');

            // Ensure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Move directly into public/uploads/logos/ — accessible on ANY tenant subdomain
            $file->move($destDir, $filename);

            // Store a root-relative path so it resolves against whatever host the browser is on
            $tenant->logo = '/uploads/logos/' . $filename;
        }

        if ($request->filled('cover_color')) {
            $tenant->cover_color = $request->cover_color;
        }
        if ($request->filled('secondary_color')) {
            $tenant->secondary_color = $request->secondary_color;
        }
        if ($request->filled('accent_color')) {
            $tenant->accent_color = $request->accent_color;
        }

        $tenant->save();

        return redirect()->back()->with('success', 'School logo & branding applied across login & portal headers successfully!');
    }

    /**
     * Remove custom school logo and revert to default graduation cap icon.
     */
    public function removeLogo()
    {
        $tenant = tenancy()->tenant;
        if ($tenant && $tenant->logo) {
            // The raw stored value is a root-relative path like /uploads/logos/filename.png
            $rawLogo = $tenant->getRawOriginal('logo') ?? $tenant->logo;

            // Normalise to a root-relative path regardless of old/new format
            if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://')) {
                $rawLogo = parse_url($rawLogo, PHP_URL_PATH);
            }

            // Strip leading /storage if it was stored that way
            $rawLogo = ltrim(str_replace('/storage/', '/storage/', $rawLogo), '/');

            // Delete from public/ directory (new storage location)
            $publicPath = public_path(ltrim($rawLogo, '/'));
            if (file_exists($publicPath)) {
                @unlink($publicPath);
            }

            $tenant->logo = null;
            $tenant->save();
        }

        return redirect()->back()->with('success', 'School logo removed. Default branding restored.');
    }

    /**
     * Store and apply School Brand Color Scheme (Primary, Secondary, Accent).
     */
    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'cover_color'           => 'required|string|max:20',
            'secondary_color'       => 'nullable|string|max:20',
            'accent_color'          => 'nullable|string|max:20',
            'tagline'               => 'nullable|string|max:255',
            'hero_heading'          => 'nullable|string|max:255',
            'hero_description'      => 'nullable|string|max:2000',
            'about_vision'          => 'nullable|string|max:5000',
            'academics_description' => 'nullable|string|max:5000',
        ]);

        $tenant = tenancy()->tenant;
        if (!$tenant) {
            return redirect()->back()->with('error', 'No active tenant school identified.');
        }

        $tenant->cover_color           = $validated['cover_color'];
        $tenant->secondary_color       = $validated['secondary_color'] ?? '#0F172A';
        $tenant->accent_color          = $validated['accent_color'] ?? '#3B82F6';
        if (isset($validated['tagline'])) {
            $tenant->tagline = $validated['tagline'];
        }
        if (isset($validated['hero_heading'])) {
            $tenant->hero_heading = $validated['hero_heading'];
        }
        if (isset($validated['hero_description'])) {
            $tenant->hero_description = $validated['hero_description'];
        }
        if (isset($validated['about_vision'])) {
            $tenant->about_vision = $validated['about_vision'];
        }
        if (isset($validated['academics_description'])) {
            $tenant->academics_description = $validated['academics_description'];
        }
        $tenant->save();

        return redirect()->back()->with('success', 'School brand color scheme and landing page content saved successfully!');
    }

    /**
     * Store Payment Gateway Configuration into Tenant Data JSON.
     */
    public function updateGateways(Request $request)
    {
        $validated = $request->validate([
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'jazzcash_merchant_id' => 'nullable|string|max:255',
            'jazzcash_password' => 'nullable|string|max:255',
            'jazzcash_hash' => 'nullable|string|max:255',
        ]);

        $tenant = tenancy()->tenant;
        if (!$tenant) {
            return redirect()->back()->with('error', 'No active tenant school identified.');
        }

        $tenant->stripe_public_key = $validated['stripe_public_key'] ?? '';
        $tenant->stripe_secret_key = $validated['stripe_secret_key'] ?? '';
        $tenant->jazzcash_merchant_id = $validated['jazzcash_merchant_id'] ?? '';
        $tenant->jazzcash_password = $validated['jazzcash_password'] ?? '';
        $tenant->jazzcash_hash = $validated['jazzcash_hash'] ?? '';
        $tenant->save();

        return redirect()->back()->with('success', 'Payment Gateway credentials saved successfully! Parents can now pay fees online.');
    }

    /**
     * Store new Academic Year session.
     */
    public function storeAcademicYear(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Toggle others to false
        \App\Models\AcademicYear::where('is_active', true)->update(['is_active' => false]);

        \App\Models\AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Academic Year Session created and marked as active.');
    }

    /**
     * Store new Term.
     */
    public function storeTerm(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        \App\Models\Term::create($validated);

        return redirect()->back()->with('success', 'Academic Term created successfully.');
    }

    /**
     * Toggle Academic Year active status.
     */
    public function toggleAcademicYearStatus($id)
    {
        $year = \App\Models\AcademicYear::findOrFail($id);
        
        if ($year->is_active) {
            $year->update(['is_active' => false]);
        } else {
            \App\Models\AcademicYear::where('is_active', true)->update(['is_active' => false]);
            $year->update(['is_active' => true]);
        }

        return redirect()->back()->with('success', 'Academic Year status updated.');
    }

    /**
     * Update admin personal contact info.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'phone' => 'nullable|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ]);

        return redirect()->back()->with('success', 'Profile contact details updated.');
    }

    /**
     * Change admin account password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'The provided current password does not match your account password.']);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['new_password']),
        ]);

        return redirect()->back()->with('success', 'Password updated securely.');
    }

    /**
     * Approve or reject a student leave request.
     */
    public function updateStudentLeaveStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status'        => 'required|in:approved,rejected',
            'admin_remarks' => 'nullable|string|max:500',
        ]);

        $leave = Leave::findOrFail($id);
        $leave->update([
            'status'         => $validated['status'],
            'admin_remarks'  => $validated['admin_remarks'] ?? null,
            'approved_by_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Student leave request ' . $validated['status'] . '.');
    }

    /**
     * Stream CSV reports matching frontend dashboard types.
     */
    public function exportReports(Request $request)
    {
        $type = $request->get('type', 'student_directory');
        $fileName = $type . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [];
        $data = [];

        if (in_array($type, ['student_directory', 'enrollment_report', 'admission_report', 'active_students', 'graduated_students', 'suspended_students', 'attendance_report', 'academic_performance', 'student_history', 'student_documents'])) {
            $query = \App\Models\Student::with(['user', 'currentClass', 'currentSection']);
            if ($type === 'active_students') {
                $query->where('status', 'Active');
            } elseif ($type === 'graduated_students') {
                $query->where('status', 'Graduated');
            } elseif ($type === 'suspended_students') {
                $query->where('status', 'Suspended');
            }
            $students = $query->get();
            $columns = ['Student ID', 'Admission Number', 'Roll Number', 'Name', 'Email', 'Phone', 'Class', 'Section', 'Status', 'Admission Date'];
            foreach ($students as $student) {
                $data[] = [
                    $student->id,
                    $student->admission_number,
                    $student->roll_number,
                    $student->user?->name,
                    $student->user?->email,
                    $student->user?->phone,
                    $student->currentClass?->name,
                    $student->currentSection?->name,
                    $student->status,
                    $student->admission_date,
                ];
            }
        }
        elseif (in_array($type, ['total_collection', 'outstanding_fees', 'overdue_payments', 'total_issued_challans', 'paid_challans', 'pending_challans', 'daily_collection', 'monthly_collection', 'annual_collection', 'fee_collection_trends', 'scholarship_reports', 'late_fee_reports', 'revenue_analytics'])) {
            $query = \App\Models\FeeChallan::with(['student.user']);
            if ($type === 'paid_challans' || $type === 'total_collection') {
                $query->where('status', 'paid');
            } elseif ($type === 'pending_challans' || $type === 'outstanding_fees') {
                $query->where('status', 'unpaid');
            }
            $challans = $query->get();
            $columns = ['Challan Number', 'Student Name', 'Fee Type', 'Month/Year', 'Issue Date', 'Due Date', 'Total Amount', 'Status'];
            foreach ($challans as $c) {
                $data[] = [
                    $c->challan_number,
                    $c->student?->user?->name,
                    $c->fee_type,
                    $c->month_year,
                    $c->issue_date,
                    $c->due_date,
                    $c->total_amount,
                    $c->status,
                ];
            }
        }
        elseif (in_array($type, ['staff_directory', 'teacher_report', 'staff_attendance_report', 'leave_report', 'department_report', 'payroll_ready'])) {
            $query = \App\Models\User::whereIn('user_type', ['admin', 'staff', 'teacher', 'receptionist', 'librarian', 'accountant']);
            if ($type === 'teacher_report') {
                $query->where('user_type', 'teacher');
            }
            $staff = $query->get();
            $columns = ['ID', 'Name', 'Email', 'Phone', 'User Type', 'Status', 'Created At'];
            foreach ($staff as $s) {
                $data[] = [
                    $s->id,
                    $s->name,
                    $s->email,
                    $s->phone,
                    $s->user_type,
                    $s->status,
                    $s->created_at,
                ];
            }
        }
        elseif (in_array($type, ['class_strength', 'subject_allocation', 'class_attendance_summary', 'performance_summary', 'timetable_report', 'promotion_report'])) {
            $sections = \App\Models\Section::with(['schoolClass', 'classTeacher'])->get();
            $columns = ['Class Name', 'Section Name', 'Class Teacher', 'Numeric Grade', 'Total Students'];
            foreach ($sections as $sec) {
                $studentsCount = \App\Models\Student::where('current_section_id', $sec->id)->count();
                $data[] = [
                    $sec->schoolClass?->name,
                    $sec->name,
                    $sec->classTeacher?->name,
                    $sec->schoolClass?->numeric_grade,
                    $studentsCount,
                ];
            }
        }
        elseif (in_array($type, ['marks_reports', 'result_analysis', 'subject_performance', 'grade_distribution', 'class_comparison', 'pass_fail_statistics'])) {
            $marks = \App\Models\ExamMark::with(['exam', 'student.user', 'subject'])->get();
            $columns = ['Exam Name', 'Student Name', 'Subject', 'Theory Obtained', 'Practical Obtained', 'Viva Obtained', 'Total Obtained', 'Grade', 'GPA'];
            foreach ($marks as $m) {
                $data[] = [
                    $m->exam?->name,
                    $m->student?->user?->name,
                    $m->subject?->name,
                    $m->theory_marks_obtained,
                    $m->practical_marks_obtained,
                    $m->viva_marks_obtained,
                    $m->total_marks_obtained,
                    $m->grade,
                    $m->gpa,
                ];
            }
        }
        else {
            $inquiries = \App\Models\AdmissionInquiry::with(['desiredClass'])->get();
            $columns = ['Inquiry Code', 'Student Name', 'Parent Name', 'Parent Phone', 'Parent Email', 'Class', 'Status', 'Created At'];
            foreach ($inquiries as $i) {
                $data[] = [
                    $i->inquiry_code,
                    $i->student_name,
                    $i->parent_name,
                    $i->parent_phone,
                    $i->parent_email,
                    $i->desiredClass?->name,
                    $i->status,
                    $i->created_at,
                ];
            }
        }

        $callback = function() use ($columns, $data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
