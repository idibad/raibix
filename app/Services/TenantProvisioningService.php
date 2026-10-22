<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeCreatedException;

class TenantProvisioningService
{
    /**
     * Create and provision a new school tenant with database, domain, roles, and default principal account.
     */
    public function provisionSchool(array $data): Tenant
    {
        // 1. Create Tenant in central DB with sanitized ID
        $tenantId = isset($data['id']) && !empty(trim($data['id']))
            ? strtolower(preg_replace('/[^a-z0-9-]/', '', $data['id']))
            : strtolower(preg_replace('/[^a-z0-9-]/', '', $data['name']));
        
        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $data['name'],
            'status' => 'active',
            'plan_id' => $data['plan_id'] ?? null,
        ]);

        // 2. Create Domain associated with tenant
        $rawDomain = strtolower(trim($data['domain'] ?? ''));
        $suffix = Tenant::getDomainSuffix();
        if (empty($rawDomain)) {
            $rawDomain = $tenantId . '.' . $suffix;
        }
        $normalizedDomain = str_contains($rawDomain, '.') ? $rawDomain : ($rawDomain . '.' . $suffix);

        $tenant->domains()->create([
            'domain' => $normalizedDomain,
        ]);

        if ($rawDomain !== $normalizedDomain) {
            $tenant->domains()->firstOrCreate(['domain' => $rawDomain]);
        }

        // Also register localhost version for local development convenience if we used an IP/nip.io
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        if (filter_var($host, FILTER_VALIDATE_IP) || str_contains($suffix, 'nip.io')) {
            $tenant->domains()->firstOrCreate([
                'domain' => $tenantId . '.localhost'
            ]);
        }

        // 3. Initialize Tenancy & Seed Roles + Default Admin within try/catch to auto-rollback if anything fails
        try {
            tenancy()->initialize($tenant);

            $this->seedTenantRolesAndPermissions();
            $this->createDefaultSchoolAdmin($data);

            tenancy()->end();
        } catch (\Throwable $e) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            \Illuminate\Support\Facades\DB::setDefaultConnection(config('database.default'));
            \Illuminate\Support\Facades\DB::reconnect(config('database.default'));

            $tenant->domains()->delete();
            $tenant->delete();

            throw $e;
        }

        return $tenant;
    }

    /**
     * Seed the 25 specific hierarchical roles and core permissions for this school.
     */
    protected function seedTenantRolesAndPermissions(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Core Permissions across modules
        $permissions = [
            'manage admissions',
            'mark attendance',
            'view child attendance',
            'manage fees',
            'pay fee online',
            'upload lms material',
            'evaluate assignments',
            'manage examinations',
            'enter marks',
            'view results',
            'manage transport',
            'manage library',
            'manage inventory',
            'send notices',
            'access ai features',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 1. Administration Portal Roles
        $adminRoles = [
            'Principal' => ['manage admissions', 'mark attendance', 'manage fees', 'manage examinations', 'manage transport', 'manage library', 'manage inventory', 'send notices', 'access ai features', 'view results'],
            'Vice Principal' => ['manage admissions', 'mark attendance', 'manage examinations', 'send notices', 'access ai features', 'view results'],
            'Academic Coordinator' => ['manage admissions', 'mark attendance', 'manage examinations', 'upload lms material', 'view results'],
            'Department Heads' => ['mark attendance', 'upload lms material', 'evaluate assignments', 'enter marks', 'view results'],
            'Accountant' => ['manage fees'],
            'Admission Officer' => ['manage admissions'],
            'Librarian' => ['manage library'],
            'Transport Manager' => ['manage transport'],
        ];

        // 2. Teaching Staff Roles
        $teachingRoles = [
            'Nursery Teachers',
            'Junior Section Teachers',
            'Middle School Teachers',
            'Secondary School Teachers',
            'College Lecturers',
            'Lab Instructors',
        ];

        // 3. Student Roles
        $studentRoles = [
            'Nursery',
            'Prep',
            'Primary (1-5)',
            'Middle (6-8)',
            'Matric (9-10)',
            'Intermediate/FSc (11-12)',
        ];

        // 4. Parent/Guardian Roles
        $parentRoles = [
            'Father',
            'Mother',
            'Guardian',
        ];

        // Create Admin Roles and assign permissions
        foreach ($adminRoles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        // Create Teaching Staff Roles and assign standard teacher permissions
        $teacherPerms = ['mark attendance', 'upload lms material', 'evaluate assignments', 'enter marks', 'view results', 'access ai features'];
        foreach ($teachingRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($teacherPerms);
        }

        // Create Student Roles and assign student permissions
        $studentPerms = ['view results'];
        foreach ($studentRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($studentPerms);
        }

        // Create Parent Roles and assign parent permissions
        $parentPerms = ['view child attendance', 'pay fee online', 'view results'];
        foreach ($parentRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($parentPerms);
        }
    }

    /**
     * Create the default Principal user inside the tenant database along with demo teacher, student, and parent accounts.
     */
    protected function createDefaultSchoolAdmin(array $data): void
    {
        $tenantId = $data['id'] ?? 'school';
        
        // 1. Create the user-defined Principal Admin
        $adminEmail = $data['admin_email'] ?? "principal@{$tenantId}.lmscloud.test";
        $adminPass = $data['admin_password'] ?? 'Secret@123';
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $data['admin_name'] ?? 'Principal Admin',
                'phone' => $data['admin_phone'] ?? '03000000000',
                'user_type' => 'admin',
                'status' => 'active',
                'password' => Hash::make($adminPass),
            ]
        );
        $admin->assignRole('Principal');

        // 2. Also ensure demo admin email exists for the Login.vue quick-tab
        $demoAdminEmail = "principal@{$tenantId}.lmscloud.test";
        if ($adminEmail !== $demoAdminEmail) {
            $demoAdmin = User::firstOrCreate(
                ['email' => $demoAdminEmail],
                [
                    'name' => 'Demo Principal',
                    'phone' => '03001111111',
                    'user_type' => 'admin',
                    'status' => 'active',
                    'password' => Hash::make('Secret@123'),
                ]
            );
            $demoAdmin->assignRole('Principal');
        }

        // 3. Create Demo Teacher account
        $teacherEmail = "teacher@{$tenantId}.lmscloud.test";
        $teacher = User::firstOrCreate(
            ['email' => $teacherEmail],
            [
                'name' => 'Ms. Ayesha Siddiqui (Demo Teacher)',
                'phone' => '03002222222',
                'user_type' => 'teacher',
                'status' => 'active',
                'password' => Hash::make('Teacher@123'),
            ]
        );
        $teacher->assignRole('Junior Section Teachers');

        // Create sample academic structure for demo users (Standard & British O/A Levels)
        $class = \App\Models\SchoolClass::firstOrCreate(
            ['name' => 'Grade 5', 'level' => 'primary'],
            ['numeric_grade' => 5]
        );
        $section = \App\Models\Section::firstOrCreate(
            ['class_id' => $class->id, 'name' => 'A'],
            ['room_number' => '101', 'class_teacher_id' => $teacher->id]
        );
        $subject = \App\Models\Subject::firstOrCreate(
            ['class_id' => $class->id, 'name' => 'Mathematics'],
            ['code' => 'MATH-501', 'type' => 'theory', 'total_marks' => 100]
        );

        // Provision O/A Level demo classes right out of the box
        $oLevelClass = \App\Models\SchoolClass::firstOrCreate(
            ['name' => 'O-Level Year 1 (I-GCSE)', 'level' => 'o_levels'],
            ['numeric_grade' => 9]
        );
        \App\Models\Section::firstOrCreate(
            ['class_id' => $oLevelClass->id, 'name' => 'Cambridge Blue'],
            ['room_number' => 'CB-101', 'class_teacher_id' => $teacher->id]
        );
        \App\Models\Subject::firstOrCreate(
            ['class_id' => $oLevelClass->id, 'name' => 'Physics (OL-PHY)'],
            ['code' => 'PHY-901', 'type' => 'theory', 'total_marks' => 100]
        );

        $aLevelClass = \App\Models\SchoolClass::firstOrCreate(
            ['name' => 'A-Level AS (Year 1)', 'level' => 'a_levels'],
            ['numeric_grade' => 12]
        );
        \App\Models\Section::firstOrCreate(
            ['class_id' => $aLevelClass->id, 'name' => 'Pre-Medical'],
            ['room_number' => 'AL-201', 'class_teacher_id' => $teacher->id]
        );

        // 4. Create Demo Parent account
        $parentEmail = "parent@{$tenantId}.lmscloud.test";
        $parentUser = User::firstOrCreate(
            ['email' => $parentEmail],
            [
                'name' => 'Mr. Tariq Mahmood (Demo Parent)',
                'phone' => '03003333333',
                'user_type' => 'parent',
                'status' => 'active',
                'password' => Hash::make('Parent@123'),
            ]
        );
        $parentUser->assignRole('Father');
        $parentsProfile = \App\Models\ParentsProfile::firstOrCreate(
            ['user_id' => $parentUser->id],
            [
                'father_name' => 'Mr. Tariq Mahmood',
                'father_phone' => '03003333333',
                'mother_name' => 'Mrs. Fatima Mahmood',
            ]
        );

        // 5. Create Demo Student account
        $studentEmail = "student@{$tenantId}.lmscloud.test";
        $studentUser = User::firstOrCreate(
            ['email' => $studentEmail],
            [
                'name' => 'Ali Tariq (Demo Student)',
                'phone' => '03004444444',
                'user_type' => 'student',
                'status' => 'active',
                'password' => Hash::make('Student@123'),
            ]
        );
        $studentUser->assignRole('Primary (1-5)');
        $student = \App\Models\Student::firstOrCreate(
            ['user_id' => $studentUser->id],
            [
                'parent_id' => $parentsProfile->id,
                'admission_number' => "ADM-2026-001",
                'roll_number' => "G5A-01",
                'admission_date' => now(),
                'current_class_id' => $class->id,
                'current_section_id' => $section->id,
                'status' => 'active',
            ]
        );

        // Create sample timetable & notice so dashboards have rich initial data
        \App\Models\Timetable::firstOrCreate(
            ['section_id' => $section->id, 'day_of_week' => date('l')],
            [
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'start_time' => '08:30:00',
                'end_time' => '09:15:00',
                'room_number' => '101',
            ]
        );
        \App\Models\Notice::firstOrCreate(
            ['title' => 'Welcome to ' . ($data['name'] ?? config('app.name', 'LMS Cloud'))],
            [
                'content' => 'Your school cloud portal is now active and ready for administrators, teachers, students, and parents.',
                'target_audience' => 'all',
                'publish_date' => now(),
                'created_by' => $admin->id,
            ]
        );
    }
}
