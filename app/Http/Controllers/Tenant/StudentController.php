<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\ParentsProfile;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudentStatusLog;
use App\Models\StudentDocument;
use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Store manual enrollment (bypassing admissions pipeline).
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'admission_number' => 'required|string|unique:students,admission_number',
            'roll_number' => 'nullable|string|max:50',
            'b_form' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'admission_date' => 'nullable|date',
            'current_class_id' => 'required|exists:classes,id',
            'current_section_id' => 'nullable|exists:sections,id',
            'parent_id' => 'nullable|exists:parents_profile,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create User account for student
            $email = $request->email ?: ('student_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $request->admission_number)) . '_' . time() . '@school.edu.pk');
            $password = Str::random(10);

            $user = User::create([
                'name' => $request->student_name,
                'email' => $email,
                'phone' => $request->phone,
                'user_type' => 'student',
                'status' => 'active',
                'password' => Hash::make($password),
            ]);

            // 2. Handle Parent / Guardian linking or creation
            $parentId = $request->parent_id;
            if (!$parentId && $request->filled('father_name')) {
                $parentUser = User::create([
                    'name' => $request->father_name,
                    'email' => 'parent_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $request->admission_number)) . '_' . time() . '@school.edu.pk',
                    'phone' => $request->father_phone,
                    'user_type' => 'parent',
                    'status' => 'active',
                    'password' => Hash::make(Str::random(10)),
                ]);

                $parentProfile = ParentsProfile::create([
                    'user_id' => $parentUser->id,
                    'father_name' => $request->father_name,
                    'father_phone' => $request->father_phone,
                ]);
                $parentId = $parentProfile->id;
            }

            // 3. Create Student profile
            $student = Student::create([
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'admission_number' => $request->admission_number,
                'roll_number' => $request->roll_number,
                'b_form' => $request->b_form,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender ?: 'male',
                'admission_date' => $request->admission_date ?: date('Y-m-d'),
                'current_class_id' => $request->current_class_id,
                'current_section_id' => $request->current_section_id,
                'status' => 'Active',
            ]);

            // 4. Create initial status log entry
            StudentStatusLog::create([
                'student_id' => $student->id,
                'changed_by_user_id' => auth()->id(),
                'old_status' => null,
                'new_status' => 'Active',
                'reason' => 'Initial manual enrollment by administrator.',
            ]);

            DB::commit();

            try {
                $loginUrl = route('tenant.login');
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WelcomeStudentMail($user, $password, $loginUrl));
            } catch (\Throwable $mailEx) {
                \Illuminate\Support\Facades\Log::error("Failed to send student welcome email: " . $mailEx->getMessage());
            }

            return redirect()->back()->with('success', "Student {$student->user->name} enrolled successfully! Admission No: {$student->admission_number}");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', "Failed to enroll student: " . $e->getMessage());
        }
    }

    /**
     * Update student demographic details and parent linking.
     */
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'student_name' => 'required|string|max:255',
            'roll_number' => 'nullable|string|max:50',
            'b_form' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'current_class_id' => 'required|exists:classes,id',
            'current_section_id' => 'nullable|exists:sections,id',
            'parent_id' => 'nullable|exists:parents_profile,id',
        ]);

        $student->user()->update([
            'name' => $request->student_name,
            'phone' => $request->phone ?: $student->user->phone,
        ]);

        $student->update([
            'roll_number' => $request->roll_number,
            'b_form' => $request->b_form,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'current_class_id' => $request->current_class_id,
            'current_section_id' => $request->current_section_id,
            'parent_id' => $request->parent_id ?: $student->parent_id,
        ]);

        return redirect()->back()->with('success', "Student profile updated successfully.");
    }

    /**
     * Update student status with mandatory audit log transition.
     */
    public function updateStatus(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:Active,On Leave,Suspended,Transferred Out,Graduated,Expelled,Inactive/Dropped',
            'reason' => 'required|string|min:3',
        ]);

        if ($student->status !== $request->status) {
            $oldStatus = $student->status;
            
            $updateData = ['status' => $request->status];
            if (in_array($request->status, ['Suspended', 'Expelled', 'Inactive/Dropped'])) {
                $updateData['suspension_reason'] = $request->reason;
                $updateData['suspended_at'] = now();
            } else if ($request->status === 'Active') {
                $updateData['suspension_reason'] = null;
                $updateData['suspended_at'] = null;
            }
            
            $student->update($updateData);

            // Sync user account status
            if (in_array($request->status, ['Suspended', 'Transferred Out', 'Graduated', 'Expelled', 'Inactive/Dropped'])) {
                $student->user()->update(['status' => 'inactive']);
            } elseif ($request->status === 'Active') {
                $student->user()->update(['status' => 'active']);
            }

            StudentStatusLog::create([
                'student_id' => $student->id,
                'changed_by_user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'reason' => $request->reason,
            ]);

            // Auto-Issue Certificate workflow
            $issuedCert = null;
            if ($request->status === 'Transferred Out') {
                $template = CertificateTemplate::where('type', 'transfer')
                    ->orWhere('name', 'like', '%Leaving%')
                    ->orWhere('name', 'like', '%Transfer%')
                    ->first();
                
                if (!$template) {
                    $template = CertificateTemplate::create([
                        'name' => 'School Leaving Certificate',
                        'type' => 'transfer',
                        'content' => "This is to certify that {{student_name}} son/daughter of {{father_name}} bearing roll number {{roll_number}} has been a student of {{school_name}} in Class {{class_name}}. They left the school on {{issue_date}} due to: " . $request->reason,
                        'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
                    ]);
                }
                
                $refNo = 'CERT-LC-' . date('Y') . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $issuedCert = IssuedCertificate::create([
                    'student_id' => $student->id,
                    'template_id' => $template->id,
                    'issue_date' => now()->format('Y-m-d'),
                    'reference_number' => $refNo,
                ]);
            } elseif ($request->status === 'Graduated') {
                $template = CertificateTemplate::where('type', 'appreciation')
                    ->orWhere('name', 'like', '%Graduation%')
                    ->orWhere('name', 'like', '%Graduate%')
                    ->first();
                
                if (!$template) {
                    $template = CertificateTemplate::create([
                        'name' => 'Graduation Certificate',
                        'type' => 'appreciation',
                        'content' => "This is to certify that {{student_name}} son/daughter of {{father_name}} bearing roll number {{roll_number}} has successfully graduated from {{school_name}} upon completing Class {{class_name}} on {{issue_date}}.",
                        'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
                    ]);
                }
                
                $refNo = 'CERT-GRAD-' . date('Y') . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $issuedCert = IssuedCertificate::create([
                    'student_id' => $student->id,
                    'template_id' => $template->id,
                    'issue_date' => now()->format('Y-m-d'),
                    'reference_number' => $refNo,
                ]);
            }

            if ($issuedCert) {
                return redirect()->back()
                    ->with('success', "Student status updated to {$request->status}. A leaving/graduation certificate has been automatically issued (Ref: {$issuedCert->reference_number})!")
                    ->with('print_certificate_url', route('tenant.admin.certificates.print', $issuedCert->id));
            }
        }

        return redirect()->back()->with('success', "Student status updated to {$request->status} and logged to audit trail.");
    }

    /**
     * Section transfer or promotion.
     */
    public function transferSection(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $oldClassName = $student->currentClass ? $student->currentClass->name : 'N/A';
        $oldSectionName = $student->currentSection ? $student->currentSection->name : 'N/A';

        $student->update([
            'current_class_id' => $request->class_id,
            'current_section_id' => $request->section_id,
        ]);

        $newClass = SchoolClass::find($request->class_id);
        $newSection = $request->section_id ? Section::find($request->section_id) : null;

        StudentStatusLog::create([
            'student_id' => $student->id,
            'changed_by_user_id' => auth()->id(),
            'old_status' => $student->status,
            'new_status' => $student->status,
            'reason' => "Transferred from {$oldClassName} ({$oldSectionName}) to {$newClass->name} (" . ($newSection ? $newSection->name : 'No Section') . ").",
        ]);

        return redirect()->back()->with('success', "Student transferred to {$newClass->name} successfully!");
    }

    /**
     * End-of-Year Bulk Promotion tool with exception list.
     */
    public function bulkPromote(Request $request)
    {
        $request->validate([
            'from_class_id' => 'required|exists:classes,id',
            'to_class_id' => 'required|exists:classes,id',
            'to_section_id' => 'nullable|exists:sections,id',
            'exception_student_ids' => 'nullable|array',
        ]);

        if ($request->from_class_id == $request->to_class_id && !$request->to_section_id) {
            return redirect()->back()->withErrors(['to_class_id' => 'Target class must be different unless transferring to a specific target section.']);
        }

        $exceptions = is_array($request->exception_student_ids) 
            ? array_map('intval', $request->exception_student_ids) 
            : [];

        $students = Student::where('current_class_id', $request->from_class_id)
            ->whereIn('status', ['Active', 'On Leave'])
            ->whereNotIn('id', $exceptions)
            ->get();

        if ($students->isEmpty()) {
            return redirect()->back()->withErrors(['from_class_id' => 'No active students found in the selected class eligible for promotion.']);
        }

        $classes = SchoolClass::whereIn('id', [$request->from_class_id, $request->to_class_id])->get()->keyBy('id');
        $sectionIds = [];
        if ($request->to_section_id) {
            $sectionIds[] = $request->to_section_id;
        }
        foreach ($students as $student) {
            if ($student->current_section_id) {
                $sectionIds[] = $student->current_section_id;
            }
        }
        $sections = Section::whereIn('id', array_unique($sectionIds))->get()->keyBy('id');

        $toClass = $classes->get($request->to_class_id);
        $toSection = $request->to_section_id ? $sections->get($request->to_section_id) : null;
        $targetDesc = ($toClass ? $toClass->name : 'N/A') . ($toSection ? " - " . $toSection->name : "");

        $count = 0;
        DB::transaction(function () use ($students, $request, $classes, $sections, $toClass, $toSection, $targetDesc, &$count) {
            foreach ($students as $student) {
                $oldStatus = $student->status ?: 'Active';
                $oldClassId = $student->current_class_id;
                $oldSectionId = $student->current_section_id;

                $oldClass = $classes->get($oldClassId);
                $oldSection = $oldSectionId ? $sections->get($oldSectionId) : null;
                $oldDesc = ($oldClass ? $oldClass->name : 'N/A') . ($oldSection ? ' - ' . $oldSection->name : '');
                $newDesc = ($toClass ? $toClass->name : 'N/A') . ($toSection ? ' - ' . $toSection->name : '');

                $student->update([
                    'current_class_id' => $request->to_class_id,
                    'current_section_id' => $request->to_section_id ?: null,
                ]);

                StudentStatusLog::create([
                    'student_id' => $student->id,
                    'changed_by_user_id' => auth()->id(),
                    'old_status' => $oldStatus,
                    'new_status' => $oldStatus,
                    'reason' => "Promoted from {$oldDesc} to {$newDesc} on " . date('Y-m-d') . ".",
                ]);

                $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();

                \App\Models\StudentPromotionLog::create([
                    'student_id' => $student->id,
                    'from_class_id' => $oldClassId,
                    'to_class_id' => $request->to_class_id,
                    'from_section_id' => $oldSectionId,
                    'to_section_id' => $request->to_section_id ?: null,
                    'promoted_by_user_id' => auth()->id(),
                    'academic_year_id' => $activeYear ? $activeYear->id : null,
                    'reason' => "Promoted from {$oldDesc} to {$newDesc}",
                ]);

                $count++;
            }
        });

        return redirect()->back()->with('success', "Bulk promotion successful! {$count} active students promoted to {$targetDesc}.");
    }

    /**
     * Upload document to Student Document Vault.
     */
    public function uploadDocument(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'document_type' => 'required|string',
            'document_file' => 'required|file|mimes:pdf,jpeg,png,jpg,webp|max:8192',
        ]);

        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $tenantId = function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'school';
            $filename = 'stu_' . $student->id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName());
            $path = $file->storeAs("tenants/{$tenantId}/students/{$student->id}/docs", $filename, 'local');

            StudentDocument::create([
                'student_id' => $student->id,
                'title' => $request->title,
                'document_type' => $request->document_type,
                'file_url' => $path,
                'uploaded_by_user_id' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', "Document '{$request->title}' uploaded to student vault securely.");
    }

    /**
     * Soft delete / archive student.
     */
    public function destroy(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $student->update(['status' => 'Inactive/Dropped']);
        $student->user()->update(['status' => 'inactive']);

        StudentStatusLog::create([
            'student_id' => $student->id,
            'changed_by_user_id' => auth()->id(),
            'old_status' => $student->status,
            'new_status' => 'Inactive/Dropped',
            'reason' => "Archived / Soft Deleted: {$request->reason}",
        ]);

        $student->delete(); // Soft delete

        return redirect()->back()->with('success', "Student profile archived. All fee and attendance history remains preserved.");
    }
}
