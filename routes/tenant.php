<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\AdminPortalController;
use App\Http\Controllers\Tenant\TeacherPortalController;
use App\Http\Controllers\Tenant\StudentPortalController;
use App\Http\Controllers\Tenant\ParentPortalController;
use App\Http\Controllers\Tenant\AdmissionController;
use App\Http\Controllers\Tenant\TenantHomeController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\StudentController;
use App\Http\Controllers\Tenant\TimetableController;
use App\Http\Controllers\Tenant\FeeStructureController;
use App\Http\Controllers\Tenant\StaffAttendanceController;
use App\Http\Controllers\Tenant\ExamGradebookController;
use App\Http\Controllers\Tenant\SportsActivityController;
use App\Http\Controllers\Tenant\PenaltyController;
use App\Http\Controllers\Tenant\ImportController;
use App\Http\Controllers\Tenant\BillingController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    \App\Http\Middleware\CheckTenantStatus::class,
    \App\Http\Middleware\LogTenantRequests::class,
])->group(function () {
    
    // Auth Routes
    Route::get('/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/login', [TenantAuthController::class, 'login'])->name('tenant.login.submit');
    Route::post('/logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');

    // Public School Landing Page
    Route::get('/', [TenantHomeController::class, 'index'])->name('tenant.home');

    // User Impersonation Handler
    Route::get('/impersonate/{token}', function (string $token) {
        return \Stancl\Tenancy\Features\UserImpersonation::makeResponse($token);
    })->name('tenant.impersonate');

    // Payment Gateway Callback IPN (unauthenticated POST from JazzCash Server)
    Route::post('/parent/payment/callback', [ParentPortalController::class, 'paymentCallback'])->name('tenant.parent.payment.callback');

    // Public Embeddable Admissions Inquiry API (for school external website)
    Route::post('/api/v1/inquiries', [AdmissionController::class, 'storeInquiry'])->name('tenant.api.inquiries.store');

    Route::middleware(['auth'])->group(function () {
        Route::get('/challans/{id}/print', [FeeStructureController::class, 'printChallan'])->name('tenant.challans.print');

        // Principal & Admin Portal
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminPortalController::class, 'index'])->name('tenant.admin.dashboard');
            
            // Admissions
            Route::get('/admissions', [AdmissionController::class, 'index'])->name('tenant.admissions.index');
            Route::post('/admissions/inquiry', [AdmissionController::class, 'storeInquiry'])->name('tenant.admissions.inquiry.store');
            Route::post('/admissions/application', [AdmissionController::class, 'storeApplication'])->name('tenant.admissions.application.store');
            Route::post('/admissions/application/{id}/merit', [AdmissionController::class, 'evaluateMerit'])->name('tenant.admissions.application.merit');
            Route::post('/admissions/application/{id}/enroll', [AdmissionController::class, 'enroll'])->name('tenant.admissions.application.enroll');
            Route::post('/admissions/challans/{challanId}/pay-manual', [AdmissionController::class, 'payChallanManual'])->name('tenant.admin.admissions.challans.pay_manual');

            // User Management by Role (P0 #1)
            Route::post('/users', [StaffController::class, 'store'])->name('tenant.admin.users.store');
            Route::put('/users/{id}', [StaffController::class, 'update'])->name('tenant.admin.users.update');
            Route::post('/users/{id}/toggle-status', [StaffController::class, 'toggleStatus'])->name('tenant.admin.users.toggle_status');
            Route::post('/users/{id}/reset-password', [StaffController::class, 'sendResetLink'])->name('tenant.admin.users.reset_password');
            Route::delete('/users/{id}', [StaffController::class, 'destroy'])->name('tenant.admin.users.destroy');
            Route::post('/users/bulk-import', [StaffController::class, 'bulkImport'])->name('tenant.admin.users.bulk_import');

            // Student Management CRUD & Lifecycle (P0 #2)
            Route::post('/students', [StudentController::class, 'store'])->name('tenant.admin.students.store');
            Route::put('/students/{id}', [StudentController::class, 'update'])->name('tenant.admin.students.update');
            Route::post('/students/{id}/status', [StudentController::class, 'updateStatus'])->name('tenant.admin.students.update_status');
            Route::post('/students/{id}/transfer', [StudentController::class, 'transferSection'])->name('tenant.admin.students.transfer');
            Route::post('/students/bulk-promote', [StudentController::class, 'bulkPromote'])->name('tenant.admin.students.bulk_promote');
            Route::post('/students/{id}/documents', [StudentController::class, 'uploadDocument'])->name('tenant.admin.students.documents.upload');
            Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('tenant.admin.students.destroy');

            // Timetable Builder & Conflict Detection (P1 #5)
            Route::get('/timetables', [TimetableController::class, 'index'])->name('tenant.admin.timetables.index');
            Route::post('/timetables', [TimetableController::class, 'store'])->name('tenant.admin.timetables.store');
            Route::delete('/timetables/{id}', [TimetableController::class, 'destroy'])->name('tenant.admin.timetables.destroy');

            // Fee Structure Engine & Recurring Invoice Generation (P1 #6)
            Route::get('/fees/structure', [FeeStructureController::class, 'index'])->name('tenant.admin.fees.structure');
            Route::post('/fees/structure/class', [FeeStructureController::class, 'updateClassStructure'])->name('tenant.admin.fees.structure.class');
            Route::post('/fees/overrides', [FeeStructureController::class, 'storeStudentOverride'])->name('tenant.admin.fees.overrides.store');
            Route::delete('/fees/overrides/{id}', [FeeStructureController::class, 'destroyStudentOverride'])->name('tenant.admin.fees.overrides.destroy');
            Route::post('/fees/generate-monthly', [FeeStructureController::class, 'generateMonthlyChallans'])->name('tenant.admin.fees.generate_monthly');
            Route::get('/fees/challans/{id}/print', [FeeStructureController::class, 'printChallan'])->name('tenant.admin.fees.challans.print');

            // Staff Attendance & Leave Management (P1 #7)
            Route::get('/staff-attendance', [StaffAttendanceController::class, 'index'])->name('tenant.admin.staff_attendance.index');
            Route::post('/staff-attendance/check-in', [StaffAttendanceController::class, 'storeCheckIn'])->name('tenant.admin.staff_attendance.check_in');
            Route::post('/staff-leaves', [StaffAttendanceController::class, 'submitLeaveRequest'])->name('tenant.admin.staff_leaves.store');
            Route::post('/staff-leaves/{id}/status', [StaffAttendanceController::class, 'updateLeaveStatus'])->name('tenant.admin.staff_leaves.update_status');
            // Student leave approve/reject
            Route::post('/student-leaves/{id}/status', [AdminPortalController::class, 'updateStudentLeaveStatus'])->name('tenant.admin.student_leaves.update_status');

            // Exam Gradebook & Term Reports (P1 #9)
            Route::get('/exams/gradebook', [ExamGradebookController::class, 'index'])->name('tenant.admin.exams.gradebook');
            Route::post('/exams', [ExamGradebookController::class, 'storeExam'])->name('tenant.admin.exams.store');
            Route::post('/exams/gradebook/bulk-save', [ExamGradebookController::class, 'bulkSaveMarks'])->name('tenant.admin.exams.gradebook.save');
            Route::get('/exams/{examId}/report-card/{studentId}', [ExamGradebookController::class, 'generateReportCard'])->name('tenant.admin.exams.report_card');

            // Classes & Sections Management
            Route::post('/classes', [AdminPortalController::class, 'storeClass'])->name('tenant.admin.classes.store');
            Route::delete('/classes/{id}', [AdminPortalController::class, 'destroyClass'])->name('tenant.admin.classes.destroy');
            Route::post('/sections', [AdminPortalController::class, 'storeSection'])->name('tenant.admin.sections.store');
            Route::delete('/sections/{id}', [AdminPortalController::class, 'destroySection'])->name('tenant.admin.sections.destroy');

            // Academic Sessions & Terms Management
            Route::post('/academic-years', [AdminPortalController::class, 'storeAcademicYear'])->name('tenant.admin.academic_years.store');
            Route::post('/academic-years/{id}/toggle-status', [AdminPortalController::class, 'toggleAcademicYearStatus'])->name('tenant.admin.academic_years.toggle');
            Route::post('/terms', [AdminPortalController::class, 'storeTerm'])->name('tenant.admin.terms.store');

            // Notice Board Circulars Management
            Route::post('/notices', [AdminPortalController::class, 'storeNotice'])->name('tenant.admin.notices.store');
            Route::delete('/notices/{id}', [AdminPortalController::class, 'destroyNotice'])->name('tenant.admin.notices.destroy');

            // Defaulters Management
            Route::post('/defaulters/{id}/remind', [AdminPortalController::class, 'sendDefaulterReminder'])->name('tenant.admin.defaulters.remind');
            Route::post('/defaulters/{id}/waive', [AdminPortalController::class, 'waiveDefaulterLateFee'])->name('tenant.admin.defaulters.waive');

            // School Settings & Branding
            Route::post('/settings/logo', [AdminPortalController::class, 'updateLogo'])->name('tenant.admin.settings.logo');
            Route::delete('/settings/logo', [AdminPortalController::class, 'removeLogo'])->name('tenant.admin.settings.logo.remove');
            Route::post('/settings/branding', [AdminPortalController::class, 'updateBranding'])->name('tenant.admin.settings.branding');
            Route::post('/settings/gateways', [AdminPortalController::class, 'updateGateways'])->name('tenant.admin.settings.gateways');

            // Payment Approvals and Renewals
            Route::post('/payments/approve/{id}', [AdminPortalController::class, 'approveFeePayment'])->name('tenant.admin.payments.approve');
            Route::post('/payments/reject/{id}', [AdminPortalController::class, 'rejectFeePayment'])->name('tenant.admin.payments.reject');
            Route::post('/subscription/renew', [AdminPortalController::class, 'renewSubscription'])->name('tenant.admin.subscription.renew');

            // Sports & Penalties Management (Admin & Staff)
            Route::post('/sports', [SportsActivityController::class, 'store'])->name('tenant.admin.sports.store');
            Route::put('/sports/{id}', [SportsActivityController::class, 'update'])->name('tenant.admin.sports.update');
            Route::delete('/sports/{id}', [SportsActivityController::class, 'destroy'])->name('tenant.admin.sports.destroy');
            Route::post('/penalties', [PenaltyController::class, 'store'])->name('tenant.admin.penalties.store');
            Route::post('/penalties/{id}/status', [PenaltyController::class, 'updateStatus'])->name('tenant.admin.penalties.status');
            Route::delete('/penalties/{id}', [PenaltyController::class, 'destroy'])->name('tenant.admin.penalties.destroy');

            // Bulk CSV Onboarding & British O/A Level Hierarchy Setup
            Route::post('/imports/students', [ImportController::class, 'importStudents'])->name('tenant.admin.imports.students');
            Route::post('/imports/users', [ImportController::class, 'importUsers'])->name('tenant.admin.imports.users');
            Route::post('/imports/classes', [ImportController::class, 'importClasses'])->name('tenant.admin.imports.classes');
            Route::post('/imports/preset-oa-levels', [ImportController::class, 'setupOALevelsPreset'])->name('tenant.admin.imports.oa_levels');

            // Billing & Subscription Management (P0 #3)
            Route::get('/billing', [BillingController::class, 'index'])->name('tenant.admin.billing.index');
            Route::post('/billing/upgrade', [BillingController::class, 'upgrade'])->name('tenant.admin.billing.upgrade');
            Route::post('/billing/referral', [BillingController::class, 'generateReferral'])->name('tenant.admin.billing.referral');

            // ---------------------------------------------------------------------
            // NEW ERP MODULES (Added for Comprehensive Coverage)
            // ---------------------------------------------------------------------
            // HR
            Route::get('/hr', [\App\Http\Controllers\Tenant\ERP\HrController::class, 'index'])->name('tenant.admin.hr.index');
            Route::post('/hr/salary-structures', [\App\Http\Controllers\Tenant\ERP\HrController::class, 'storeSalaryStructure'])->name('tenant.admin.hr.salary_structures.store');
            Route::post('/hr/payroll/generate', [\App\Http\Controllers\Tenant\ERP\HrController::class, 'generatePayroll'])->name('tenant.admin.hr.payroll.generate');
            Route::post('/hr/payslips/{id}/pay', [\App\Http\Controllers\Tenant\ERP\HrController::class, 'payPayslip'])->name('tenant.admin.hr.payslips.pay');

            // Finance
            Route::get('/finance-reports', [\App\Http\Controllers\Tenant\ERP\FinanceController::class, 'index'])->name('tenant.admin.finance.index');
            Route::post('/finance/vendors', [\App\Http\Controllers\Tenant\ERP\FinanceController::class, 'storeVendor'])->name('tenant.admin.finance.vendors.store');
            Route::post('/finance/expenses', [\App\Http\Controllers\Tenant\ERP\FinanceController::class, 'storeExpense'])->name('tenant.admin.finance.expenses.store');
            Route::post('/finance/refunds/{id}', [\App\Http\Controllers\Tenant\ERP\FinanceController::class, 'updateRefundRequest'])->name('tenant.admin.finance.refunds.update');

            // Library
            Route::get('/library', [\App\Http\Controllers\Tenant\ERP\LibraryController::class, 'index'])->name('tenant.admin.library.index');
            Route::post('/library/books', [\App\Http\Controllers\Tenant\ERP\LibraryController::class, 'storeBook'])->name('tenant.admin.library.books.store');
            Route::post('/library/issue', [\App\Http\Controllers\Tenant\ERP\LibraryController::class, 'issueBook'])->name('tenant.admin.library.issue.store');
            Route::post('/library/issues/{id}/return', [\App\Http\Controllers\Tenant\ERP\LibraryController::class, 'returnBook'])->name('tenant.admin.library.issue.return');

            // Transport
            Route::get('/transport', [\App\Http\Controllers\Tenant\ERP\TransportController::class, 'index'])->name('tenant.admin.transport.index');
            Route::post('/transport/vehicles', [\App\Http\Controllers\Tenant\ERP\TransportController::class, 'storeVehicle'])->name('tenant.admin.transport.vehicles.store');
            Route::post('/transport/routes', [\App\Http\Controllers\Tenant\ERP\TransportController::class, 'storeRoute'])->name('tenant.admin.transport.routes.store');
            Route::post('/transport/stops', [\App\Http\Controllers\Tenant\ERP\TransportController::class, 'storeStop'])->name('tenant.admin.transport.stops.store');
            Route::post('/transport/allocate', [\App\Http\Controllers\Tenant\ERP\TransportController::class, 'allocateTransport'])->name('tenant.admin.transport.allocate');

            // Hostels
            Route::get('/hostels', [\App\Http\Controllers\Tenant\ERP\HostelController::class, 'index'])->name('tenant.admin.hostels.index');
            Route::post('/hostels', [\App\Http\Controllers\Tenant\ERP\HostelController::class, 'storeHostel'])->name('tenant.admin.hostels.store');
            Route::post('/hostels/rooms', [\App\Http\Controllers\Tenant\ERP\HostelController::class, 'storeRoom'])->name('tenant.admin.hostels.rooms.store');
            Route::post('/hostels/allocate', [\App\Http\Controllers\Tenant\ERP\HostelController::class, 'allocateRoom'])->name('tenant.admin.hostels.allocate');
            Route::post('/hostels/allocations/{id}/vacate', [\App\Http\Controllers\Tenant\ERP\HostelController::class, 'vacateRoom'])->name('tenant.admin.hostels.vacate');

            // Inventory
            Route::get('/inventory', [\App\Http\Controllers\Tenant\ERP\InventoryController::class, 'index'])->name('tenant.admin.inventory.index');
            Route::post('/inventory/categories', [\App\Http\Controllers\Tenant\ERP\InventoryController::class, 'storeCategory'])->name('tenant.admin.inventory.categories.store');
            Route::post('/inventory/items', [\App\Http\Controllers\Tenant\ERP\InventoryController::class, 'storeItem'])->name('tenant.admin.inventory.items.store');
            Route::post('/inventory/issue', [\App\Http\Controllers\Tenant\ERP\InventoryController::class, 'issueItem'])->name('tenant.admin.inventory.issue.store');
            Route::post('/inventory/issues/{id}/status', [\App\Http\Controllers\Tenant\ERP\InventoryController::class, 'updateIssueStatus'])->name('tenant.admin.inventory.issues.status');

            // Calendar
            Route::get('/calendar', [\App\Http\Controllers\Tenant\ERP\CalendarController::class, 'index'])->name('tenant.admin.calendar.index');
            Route::post('/calendar', [\App\Http\Controllers\Tenant\ERP\CalendarController::class, 'store'])->name('tenant.admin.calendar.store');
            Route::delete('/calendar/{id}', [\App\Http\Controllers\Tenant\ERP\CalendarController::class, 'destroy'])->name('tenant.admin.calendar.destroy');

            // Certificates
            Route::get('/certificates', [\App\Http\Controllers\Tenant\ERP\CertificateController::class, 'index'])->name('tenant.admin.certificates.index');
            Route::post('/certificates/templates', [\App\Http\Controllers\Tenant\ERP\CertificateController::class, 'storeTemplate'])->name('tenant.admin.certificates.templates.store');
            Route::post('/certificates/issue', [\App\Http\Controllers\Tenant\ERP\CertificateController::class, 'issueCertificate'])->name('tenant.admin.certificates.issue');
            Route::post('/certificates/bulk-issue', [\App\Http\Controllers\Tenant\ERP\CertificateController::class, 'bulkIssue'])->name('tenant.admin.certificates.bulk-issue');
            Route::get('/certificates/issued/{id}/print', [\App\Http\Controllers\Tenant\ERP\CertificateController::class, 'print'])->name('tenant.admin.certificates.print');
            Route::get('/certificates/bulk-print', [\App\Http\Controllers\Tenant\ERP\CertificateController::class, 'printBulk'])->name('tenant.admin.certificates.bulk-print');

            // Reports CSV Export API
            Route::get('/api/reports/export', [AdminPortalController::class, 'exportReports'])->name('tenant.admin.reports.export');

            // Settings
            Route::get('/settings-erp', [\App\Http\Controllers\Tenant\ERP\ErpSettingsController::class, 'index'])->name('tenant.admin.settings_erp.index');
            Route::post('/settings-erp', [\App\Http\Controllers\Tenant\ERP\ErpSettingsController::class, 'update'])->name('tenant.admin.settings_erp.update');
            Route::post('/profile/update', [AdminPortalController::class, 'updateProfile'])->name('tenant.admin.profile.update');
            Route::post('/profile/password', [AdminPortalController::class, 'updatePassword'])->name('tenant.admin.profile.password');

            // Chat
            Route::get('/chat', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'index'])->name('tenant.admin.chat.index');
            Route::post('/chat', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'store'])->name('tenant.admin.chat.store');
            Route::post('/chat/{id}/reply', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'reply'])->name('tenant.admin.chat.reply');
        });

        // Teacher Staff Portal
        Route::prefix('teacher')->group(function () {
            Route::get('/dashboard', [TeacherPortalController::class, 'index'])->name('tenant.teacher.dashboard');
            Route::get('/attendance', [TeacherPortalController::class, 'showAttendance'])->name('tenant.teacher.attendance');
            Route::post('/attendance', [TeacherPortalController::class, 'storeAttendance'])->name('tenant.teacher.attendance.store');
            Route::post('/attendance/ai-snapshot', [TeacherPortalController::class, 'processAiAttendance'])->name('tenant.teacher.attendance.ai');
            Route::post('/materials', [TeacherPortalController::class, 'storeMaterial'])->name('tenant.teacher.materials.store');
            Route::post('/assignments', [TeacherPortalController::class, 'storeAssignment'])->name('tenant.teacher.assignments.store');
            Route::post('/submissions/{id}/grade', [TeacherPortalController::class, 'gradeSubmission'])->name('tenant.teacher.submissions.grade');
            Route::post('/submissions/{id}/ai-evaluate', [TeacherPortalController::class, 'aiEvaluateSubmission'])->name('tenant.teacher.submissions.ai_evaluate');
            Route::post('/marks', [TeacherPortalController::class, 'storeMarks'])->name('tenant.teacher.marks.store');
            Route::post('/observations', [TeacherPortalController::class, 'storeObservation'])->name('tenant.teacher.observations.store');
            Route::post('/leaves', [TeacherPortalController::class, 'storeLeaveRequest'])->name('tenant.teacher.leaves.store');
            // Student leave approve/reject by class teacher
            Route::post('/student-leaves/{id}/status', [TeacherPortalController::class, 'updateStudentLeaveStatus'])->name('tenant.teacher.student_leaves.update_status');
            Route::post('/sports', [SportsActivityController::class, 'store'])->name('tenant.teacher.sports.store');
            Route::put('/sports/{id}', [SportsActivityController::class, 'update'])->name('tenant.teacher.sports.update');
            Route::delete('/sports/{id}', [SportsActivityController::class, 'destroy'])->name('tenant.teacher.sports.destroy');
            Route::post('/penalties', [PenaltyController::class, 'store'])->name('tenant.teacher.penalties.store');
            Route::post('/penalties/{id}/status', [PenaltyController::class, 'updateStatus'])->name('tenant.teacher.penalties.status');
            Route::delete('/penalties/{id}', [PenaltyController::class, 'destroy'])->name('tenant.teacher.penalties.destroy');

            // Chat
            Route::get('/chat', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'index'])->name('tenant.teacher.chat.index');
            Route::post('/chat', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'store'])->name('tenant.teacher.chat.store');
            Route::post('/chat/{id}/reply', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'reply'])->name('tenant.teacher.chat.reply');
            Route::post('/profile/update', [TeacherPortalController::class, 'updateProfile'])->name('tenant.teacher.profile.update');
            Route::post('/profile/password', [TeacherPortalController::class, 'updatePassword'])->name('tenant.teacher.profile.password');
        });

        // Payment Processing (Stripe Gateway)
        Route::post('/payments/checkout/{challan}', [\App\Http\Controllers\Tenant\PaymentController::class, 'checkout'])->name('tenant.payments.checkout');
        Route::get('/payments/success', [\App\Http\Controllers\Tenant\PaymentController::class, 'success'])->name('tenant.payments.success');

        // Student Portal
        Route::prefix('student')->group(function () {
            Route::get('/dashboard', [StudentPortalController::class, 'index'])->name('tenant.student.dashboard');
            Route::post('/assignments/{id}/submit', [StudentPortalController::class, 'submitAssignment'])->name('tenant.student.assignments.submit');
            Route::post('/assignments/{id}/appeal', [StudentPortalController::class, 'submitAppeal'])->name('tenant.student.assignments.appeal');
            Route::post('/assignments/{id}/question', [StudentPortalController::class, 'askQuestion'])->name('tenant.student.assignments.question');
            Route::post('/materials/{id}/bookmark', [StudentPortalController::class, 'toggleBookmark'])->name('tenant.student.materials.bookmark');
            Route::post('/notices/{id}/read', [StudentPortalController::class, 'markNoticeRead'])->name('tenant.student.notices.read');
            Route::post('/leaves', [StudentPortalController::class, 'requestLeave'])->name('tenant.student.leaves.store');
            Route::post('/profile/update', [StudentPortalController::class, 'updateProfile'])->name('tenant.student.profile.update');
            Route::post('/profile/password', [StudentPortalController::class, 'updatePassword'])->name('tenant.student.profile.password');
            Route::get('/exams/{examId}/report-card', [StudentPortalController::class, 'downloadReportCard'])->name('tenant.student.exams.report_card');
            Route::post('/sports/{id}/join', [SportsActivityController::class, 'join'])->name('tenant.student.sports.join');
            Route::post('/sports/{id}/leave', [SportsActivityController::class, 'leave'])->name('tenant.student.sports.leave');
        });

        // Parent Guardian Portal
        Route::prefix('parent')->group(function () {
            Route::get('/dashboard', [ParentPortalController::class, 'index'])->name('tenant.parent.dashboard');
            Route::post('/challans/{id}/pay', [ParentPortalController::class, 'payChallan'])->name('tenant.parent.challans.pay');
            Route::post('/leaves', [ParentPortalController::class, 'submitLeaveRequest'])->name('tenant.parent.leaves.store');

            // Chat
            Route::get('/chat', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'index'])->name('tenant.parent.chat.index');
            Route::post('/chat', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'store'])->name('tenant.parent.chat.store');
            Route::post('/chat/{id}/reply', [\App\Http\Controllers\Tenant\ParentTeacherChatController::class, 'reply'])->name('tenant.parent.chat.reply');
            Route::post('/profile/update', [ParentPortalController::class, 'updateProfile'])->name('tenant.parent.profile.update');
            Route::post('/profile/password', [ParentPortalController::class, 'updatePassword'])->name('tenant.parent.profile.password');
        });
    });
});
