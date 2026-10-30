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
        
        $tenantAttributes = [
            'id' => $tenantId,
            'name' => $data['name'],
            'status' => 'active',
            'plan_id' => $data['plan_id'] ?? null,
        ];

        if (!empty($data['phone']) || !empty($data['admin_phone'])) {
            $tenantAttributes['phone'] = $data['phone'] ?? $data['admin_phone'];
        }
        if (!empty($data['email']) || !empty($data['admin_email'])) {
            $tenantAttributes['email'] = $data['email'] ?? $data['admin_email'];
        }
        if (!empty($data['address'])) {
            $tenantAttributes['address'] = $data['address'];
        }

        $tenant = Tenant::create($tenantAttributes);

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
     * Create the designated Principal/School Admin user inside the tenant database.
     * Strictly creates only the requested school administrator account without any dummy or demo users.
     */
    protected function createDefaultSchoolAdmin(array $data): void
    {
        $tenantId = $data['id'] ?? 'school';
        
        // 1. Create the user-defined Principal Admin only
        $adminEmail = $data['admin_email'] ?? "principal@{$tenantId}." . Tenant::getDomainSuffix();
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

        // 2. Initial official announcement for the school portal
        \App\Models\Notice::firstOrCreate(
            ['title' => 'Welcome to ' . ($data['name'] ?? config('app.name', 'School Portal'))],
            [
                'content' => 'Your school cloud portal is now active and ready for administrators, teachers, students, and parents.',
                'target_audience' => 'all',
                'publish_date' => now(),
                'created_by' => $admin->id,
            ]
        );
    }
}
