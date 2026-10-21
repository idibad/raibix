<?php

namespace App\Jobs;

use App\Models\ParentsProfile;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $rows;
    public ?string $tenantId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(array $rows, ?string $tenantId = null)
    {
        $this->rows = $rows;
        $this->tenantId = $tenantId ?: (function_exists('tenant') && tenant() ? tenant()->getTenantKey() : null);
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        if ($this->tenantId && (!function_exists('tenant') || !tenant() || tenant()->getTenantKey() !== $this->tenantId)) {
            tenancy()->initialize($this->tenantId);
        }

        $successCount = 0;

        DB::beginTransaction();
        try {
            foreach ($this->rows as $data) {
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

                try {
                    $studentRole = Role::firstOrCreate(['name' => 'Matric (9-10)', 'guard_name' => 'web']);
                    $user->assignRole($studentRole);
                } catch (\Throwable $e) {
                    // Spatie permission table safety
                }

                // Create Student Profile
                $student = Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'admission_number' => trim($data['admission_number'] ?? ('ADM-' . rand(1000, 9999))),
                        'roll_number' => !empty($rollNumber) ? $rollNumber : (string) rand(100, 999),
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
                $parentEmail = trim($data['parent_email'] ?? ("parent.{$student->id}@" . (function_exists('tenant') && tenant() ? tenant()->id : 'school') . ".lms.test"));

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

                    try {
                        $parentRole = Role::firstOrCreate(['name' => 'Guardian', 'guard_name' => 'web']);
                        $parentUser->assignRole($parentRole);
                    } catch (\Throwable $e) {}

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
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $successCount;
    }
}
