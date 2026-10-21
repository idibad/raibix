<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\ParentsProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ImportController extends Controller
{
    /**
     * Import Students via CSV file upload.
     */
    public function importStudents(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return redirect()->back()->with('error', 'Unable to read CSV file header.');
        }

        // Clean up BOM and whitespace on header row
        $header = array_map(function ($col) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col)));
        }, $header);

        $successCount = 0;
        $errorRows = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < count($header)) {
                    continue; // skip incomplete row
                }

                $data = array_combine($header, array_slice($row, 0, count($header)));
                
                $name = trim($data['name'] ?? '');
                $email = trim($data['email'] ?? '');
                $rollNumber = trim($data['roll_number'] ?? '');
                $className = trim($data['class_name'] ?? '');
                $sectionName = trim($data['section_name'] ?? 'A');

                if (empty($name) || empty($email)) {
                    continue;
                }

                // Provision Class & Section automatically if new school
                $class = SchoolClass::firstOrCreate(
                    ['name' => !empty($className) ? $className : 'General Class'],
                    ['level' => 'primary', 'numeric_grade' => 1]
                );

                $section = Section::firstOrCreate(
                    ['class_id' => $class->id, 'name' => !empty($sectionName) ? $sectionName : 'A'],
                    ['room_number' => '101']
                );

                // Create Student User
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'phone' => trim($data['phone'] ?? '03000000000'),
                        'user_type' => 'student',
                        'status' => 'active',
                        'password' => Hash::make('Student@123'),
                    ]
                );

                $studentRole = Role::firstOrCreate(['name' => 'Matric (9-10)', 'guard_name' => 'web']);
                $user->assignRole($studentRole);

                // Create Student Profile
                $student = Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'admission_number' => trim($data['admission_number'] ?? ('ADM-' . rand(1000, 9999))),
                        'roll_number' => !empty($rollNumber) ? $rollNumber : (string)rand(100, 999),
                        'current_class_id' => $class->id,
                        'current_section_id' => $section->id,
                        'gender' => trim($data['gender'] ?? 'Not Specified'),
                        'date_of_birth' => trim($data['date_of_birth'] ?? '2010-01-01'),
                        'status' => 'active',
                    ]
                );

                // Handle Optional Parent linkage
                $parentName = trim($data['parent_name'] ?? '');
                $parentPhone = trim($data['parent_phone'] ?? '');
                $parentEmail = trim($data['parent_email'] ?? ("parent.{$student->id}@" . (tenancy()->tenant ? tenancy()->tenant->id : 'school') . ".lms.test"));

                if (!empty($parentName)) {
                    $parentUser = User::firstOrCreate(
                        ['email' => $parentEmail],
                        [
                            'name' => $parentName,
                            'phone' => !empty($parentPhone) ? $parentPhone : '03000000000',
                            'user_type' => 'parent',
                            'status' => 'active',
                            'password' => Hash::make('Parent@123'),
                        ]
                    );
                    $parentRole = Role::firstOrCreate(['name' => 'Guardian', 'guard_name' => 'web']);
                    $parentUser->assignRole($parentRole);

                    $parentProfile = ParentsProfile::firstOrCreate(
                        ['user_id' => $parentUser->id],
                        [
                            'father_name' => $parentName,
                            'father_phone' => !empty($parentPhone) ? $parentPhone : '03000000000',
                        ]
                    );

                    $student->update(['parent_id' => $parentProfile->id]);
                }

                $successCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->back()->with('error', 'CSV Import Error: ' . $e->getMessage());
        }

        fclose($handle);
        return redirect()->back()->with('success', "Successfully onboarded {$successCount} student profiles with class & section mappings via CSV!");
    }

    /**
     * Import Teachers & Staff via CSV file upload.
     */
    public function importUsers(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return redirect()->back()->with('error', 'Unable to read CSV file header.');
        }

        $header = array_map(function ($col) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col)));
        }, $header);

        $successCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < count($header)) continue;

                $data = array_combine($header, array_slice($row, 0, count($header)));
                
                $name = trim($data['name'] ?? '');
                $email = trim($data['email'] ?? '');
                $userType = trim($data['user_type'] ?? 'staff'); // staff, teacher, accountant, librarian

                if (empty($name) || empty($email)) continue;

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'phone' => trim($data['phone'] ?? '03001112233'),
                        'user_type' => $userType,
                        'status' => 'active',
                        'password' => Hash::make('Welcome@123'),
                    ]
                );

                // Assign role based on user_type or designation
                $designation = strtolower(trim($data['designation'] ?? $userType));
                if (str_contains($designation, 'teacher') || $userType === 'teacher' || $userType === 'staff') {
                    $role = Role::firstOrCreate(['name' => 'Secondary School Teachers', 'guard_name' => 'web']);
                    $user->assignRole($role);
                } elseif ($userType === 'accountant') {
                    $role = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
                    $user->assignRole($role);
                } elseif ($userType === 'librarian') {
                    $role = Role::firstOrCreate(['name' => 'Librarian', 'guard_name' => 'web']);
                    $user->assignRole($role);
                }

                $successCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->back()->with('error', 'CSV Import Error: ' . $e->getMessage());
        }

        fclose($handle);
        return redirect()->back()->with('success', "Successfully imported {$successCount} staff & teacher profiles via CSV!");
    }

    /**
     * Import Classes, Sections & Subjects via CSV.
     */
    public function importClasses(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return redirect()->back()->with('error', 'Unable to read CSV file header.');
        }

        $header = array_map(function ($col) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col)));
        }, $header);

        $classCount = 0;
        $sectionCount = 0;
        $subjectCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < count($header)) continue;

                $data = array_combine($header, array_slice($row, 0, count($header)));
                
                $className = trim($data['name'] ?? '');
                $level = trim($data['level'] ?? 'primary');
                $numericGrade = intval($data['numeric_grade'] ?? 1);

                if (empty($className)) continue;

                $class = SchoolClass::firstOrCreate(
                    ['name' => $className],
                    ['level' => strtolower($level), 'numeric_grade' => $numericGrade]
                );
                $classCount++;

                // Split sections string
                $sectionsStr = trim($data['sections'] ?? 'A, B');
                if (!empty($sectionsStr)) {
                    $secList = array_map('trim', explode(',', $sectionsStr));
                    foreach ($secList as $secName) {
                        if (empty($secName)) continue;
                        Section::firstOrCreate(
                            ['class_id' => $class->id, 'name' => $secName],
                            ['room_number' => '101']
                        );
                        $sectionCount++;
                    }
                }

                // Split subjects string
                $subjectsStr = trim($data['subjects'] ?? '');
                if (!empty($subjectsStr)) {
                    $subList = array_map('trim', explode(',', $subjectsStr));
                    foreach ($subList as $subName) {
                        if (empty($subName)) continue;
                        Subject::firstOrCreate(
                            ['class_id' => $class->id, 'name' => $subName],
                            ['code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subName), 0, 3)) . "-{$numericGrade}01", 'type' => 'theory', 'total_marks' => 100]
                        );
                        $subjectCount++;
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->back()->with('error', 'CSV Import Error: ' . $e->getMessage());
        }

        fclose($handle);
        return redirect()->back()->with('success', "Successfully provisioned {$classCount} classes, {$sectionCount} sections, and {$subjectCount} subjects!");
    }

    /**
     * One-click British Curriculum O/A Level Hierarchy Setup Preset.
     */
    public function setupOALevelsPreset()
    {
        DB::beginTransaction();
        try {
            $presets = [
                [
                    'name' => 'O-Level Year 1 (I-GCSE)',
                    'level' => 'o_levels',
                    'numeric_grade' => 9,
                    'sections' => ['Blue', 'Green', 'Red'],
                    'subjects' => ['Physics (OL-PHY)', 'Chemistry (OL-CHM)', 'Biology (OL-BIO)', 'Mathematics (OL-MAT)', 'English Language (OL-ENG)', 'Pakistan Studies (OL-PST)', 'Islamiat (OL-ISL)']
                ],
                [
                    'name' => 'O-Level Year 2 (I-GCSE)',
                    'level' => 'o_levels',
                    'numeric_grade' => 10,
                    'sections' => ['Blue', 'Green'],
                    'subjects' => ['Physics', 'Chemistry', 'Biology', 'Mathematics', 'English Language', 'Economics', 'Computer Science']
                ],
                [
                    'name' => 'O-Level Year 3 (Final I-GCSE)',
                    'level' => 'o_levels',
                    'numeric_grade' => 11,
                    'sections' => ['Blue', 'Green'],
                    'subjects' => ['Advanced O-Level Physics', 'Advanced O-Level Chemistry', 'Pure Math', 'English Literature', 'Urdu / Regional']
                ],
                [
                    'name' => 'A-Level AS (Year 1)',
                    'level' => 'a_levels',
                    'numeric_grade' => 12,
                    'sections' => ['Pre-Medical', 'Pre-Engineering', 'Commerce / Humanities'],
                    'subjects' => ['AS Physics (9702)', 'AS Chemistry (9701)', 'AS Biology (9700)', 'AS Pure Mathematics (9709)', 'AS Economics (9708)', 'AS Accounting (9706)']
                ],
                [
                    'name' => 'A-Level A2 (Year 2)',
                    'level' => 'a_levels',
                    'numeric_grade' => 13,
                    'sections' => ['Pre-Medical', 'Pre-Engineering', 'Commerce / Humanities'],
                    'subjects' => ['A2 Physics', 'A2 Chemistry', 'A2 Biology', 'A2 Pure Mathematics & Mechanics', 'A2 Economics', 'A2 Business Studies']
                ],
            ];

            $createdClasses = 0;
            $createdSections = 0;
            $createdSubjects = 0;

            foreach ($presets as $item) {
                $class = SchoolClass::firstOrCreate(
                    ['name' => $item['name']],
                    ['level' => $item['level'], 'numeric_grade' => $item['numeric_grade']]
                );
                $createdClasses++;

                foreach ($item['sections'] as $secName) {
                    Section::firstOrCreate(
                        ['class_id' => $class->id, 'name' => $secName],
                        ['room_number' => 'Cambridge Block']
                    );
                    $createdSections++;
                }

                foreach ($item['subjects'] as $subName) {
                    Subject::firstOrCreate(
                        ['class_id' => $class->id, 'name' => $subName],
                        ['code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subName), 0, 3)) . "-{$item['numeric_grade']}01", 'type' => 'theory', 'total_marks' => 100]
                    );
                    $createdSubjects++;
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "⚡ British Curriculum O/A Level Hierarchy Preset successfully provisioned! ({$createdClasses} classes, {$createdSections} sections, and {$createdSubjects} subjects added).");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error setting up O/A Level preset: ' . $e->getMessage());
        }
    }
}
