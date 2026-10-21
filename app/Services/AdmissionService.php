<?php

namespace App\Services;

use App\Models\AdmissionInquiry;
use App\Models\AdmissionApplication;
use App\Models\Student;
use App\Models\ParentsProfile;
use App\Models\User;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdmissionService
{
    /**
     * Create an admission inquiry and schedule automated follow-up.
     */
    public function createInquiry(array $data): AdmissionInquiry
    {
        $code = 'INQ-' . date('Y') . '-' . str_pad(AdmissionInquiry::count() + 1, 4, '0', STR_PAD_LEFT);

        return AdmissionInquiry::create([
            'inquiry_code' => $code,
            'student_name' => $data['student_name'],
            'previous_school' => $data['previous_school'] ?? null,
            'desired_class_id' => $data['desired_class_id'],
            'parent_name' => $data['parent_name'],
            'parent_phone' => $data['parent_phone'],
            'parent_email' => $data['parent_email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'follow_up_date' => now()->addDays(3),
            'status' => 'new',
        ]);
    }

    /**
     * Submit an admission application with required category documents.
     */
    public function createApplication(array $data): AdmissionApplication
    {
        $code = 'APP-' . date('Y') . '-' . str_pad(AdmissionApplication::count() + 1, 4, '0', STR_PAD_LEFT);

        $application = AdmissionApplication::create([
            'application_code' => $code,
            'inquiry_id' => $data['inquiry_id'] ?? null,
            'student_name' => $data['student_name'],
            'class_id' => $data['class_id'],
            'father_name' => $data['father_name'],
            'father_cnic' => $data['father_cnic'] ?? null,
            'father_phone' => $data['father_phone'],
            'mother_name' => $data['mother_name'] ?? null,
            'mother_cnic' => $data['mother_cnic'] ?? null,
            'documents' => $data['documents'] ?? [],
            'test_date' => $data['test_date'] ?? null,
            'admission_status' => 'applied',
        ]);

        if ($application->inquiry_id) {
            AdmissionInquiry::where('id', $application->inquiry_id)->update(['status' => 'scheduled']);
        }

        return $application;
    }

    /**
     * Evaluate admission test marks and make merit decision.
     */
    public function decideMerit(AdmissionApplication $application, float $marksObtained, float $totalMarks = 100.00): string
    {
        $percentage = ($marksObtained / $totalMarks) * 100;
        $meritStatus = $percentage >= 50.0 ? 'approved' : 'rejected';

        $application->update([
            'test_marks_obtained' => $marksObtained,
            'test_total_marks' => $totalMarks,
            'merit_status' => $meritStatus,
            'admission_status' => $meritStatus === 'approved' ? 'fee_pending' : 'rejected',
        ]);

        if ($meritStatus === 'approved') {
            app(FeeService::class)->generateAdmissionChallan($application);
        }

        return $meritStatus;
    }

    /**
     * Enroll student after fee payment verification: assigns ID, roll number, section, and user accounts.
     */
    public function enrollStudent(AdmissionApplication $application): Student
    {
        if ($application->admission_status === 'enrolled') {
            throw new \Exception("This admission application [{$application->application_code}] has already been enrolled.");
        }

        return DB::transaction(function () use ($application) {
            $pendingStudent = Student::where('admission_number', 'ADM-TEMP-' . $application->id)->first();

            // 1. Create Parent User Account
            $parentEmail = strtolower(str_replace(' ', '.', $application->father_name)) . '.parent@school.test';
            $parentUser = User::firstOrCreate(
                ['phone' => $application->father_phone],
                [
                    'name' => $application->father_name,
                    'email' => $parentEmail,
                    'cnic' => $application->father_cnic,
                    'user_type' => 'parent',
                    'status' => 'active',
                    'password' => Hash::make('Parent@123'),
                ]
            );
            $parentUser->assignRole('Father');

            // 2. Create/Link ParentsProfile
            $parentsProfile = ParentsProfile::firstOrCreate(
                ['user_id' => $parentUser->id],
                [
                    'father_name' => $application->father_name,
                    'father_cnic' => $application->father_cnic,
                    'father_phone' => $application->father_phone,
                    'mother_name' => $application->mother_name,
                    'mother_cnic' => $application->mother_cnic,
                ]
            );

            // 3. Reuse the pending student account created with the admission challan,
            // or create one for applications that predate the pending-account flow.
            $studentAdmissionNumber = 'STD-' . date('Y') . '-' . str_pad(Student::where('status', '!=', 'fee_pending')->count() + 1, 4, '0', STR_PAD_LEFT);
            $studentUser = $pendingStudent?->user;
            if (!$studentUser) {
                $studentEmail = strtolower(str_replace(' ', '.', $application->student_name)) . '.' . rand(100, 999) . '@student.test';
                $studentUser = User::create([
                    'name' => $application->student_name,
                    'email' => $studentEmail,
                    'user_type' => 'student',
                    'status' => 'active',
                    'password' => Hash::make('Student@123'),
                ]);
            } else {
                $studentUser->update(['status' => 'active', 'name' => $application->student_name]);
            }

            // Assign exact Student role based on class level if possible, default 'Primary (1-5)'
            $studentUser->assignRole('Primary (1-5)');

            // 4. Allocate to Section with lowest student count in the class
            $section = Section::where('class_id', $application->class_id)->withCount('students')->orderBy('students_count', 'asc')->first();
            $sectionId = $section ? $section->id : null;
            $rollNumber = $section ? ($section->name . '-' . ($section->students_count + 1)) : ('R-' . rand(1, 99));

            // 5. Check if dummy student record from fee challan exists and update it, else create new
            $student = Student::where('admission_number', 'ADM-TEMP-' . $application->id)->first();
            if ($student) {
                $student->update([
                    'user_id' => $studentUser->id,
                    'parent_id' => $parentsProfile->id,
                    'admission_number' => $studentAdmissionNumber,
                    'roll_number' => $rollNumber,
                    'admission_date' => now(),
                    'current_class_id' => $application->class_id,
                    'current_section_id' => $sectionId,
                    'status' => 'active',
                ]);
            } else {
                $student = Student::create([
                    'user_id' => $studentUser->id,
                    'parent_id' => $parentsProfile->id,
                    'admission_number' => $studentAdmissionNumber,
                    'roll_number' => $rollNumber,
                    'admission_date' => now(),
                    'current_class_id' => $application->class_id,
                    'current_section_id' => $sectionId,
                    'status' => 'active',
                ]);
            }

            $application->update(['admission_status' => 'enrolled']);

            return $student;
        });
    }
}
