<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthorizationRBACSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testschoolrbac' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test RBAC Campus',
            'admin_email' => 'admin@school.test',
            'admin_password' => 'Secret@123',
        ]);

        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    /**
     * Test unauthenticated access redirects to login.
     */
    public function test_unauthenticated_user_cannot_access_admin_portal(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/admin/dashboard");
        $response->assertRedirect("http://{$domain}/login");
    }

    /**
     * Test student role is forbidden from admin dashboard.
     */
    public function test_student_cannot_access_admin_dashboard(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $studentUser = User::create([
            'name' => 'Test Student User',
            'email' => 'student.rbac@test.com',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($studentUser)->get("http://{$domain}/admin/dashboard");
        $response->assertStatus(403);
    }

    /**
     * Test parent role is forbidden from teacher dashboard.
     */
    public function test_parent_cannot_access_teacher_dashboard(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $parentUser = User::create([
            'name' => 'Test Parent User',
            'email' => 'parent.rbac@test.com',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($parentUser)->get("http://{$domain}/teacher/dashboard");
        $response->assertStatus(403);
    }

    /**
     * Test teacher role is forbidden from admin dashboard.
     */
    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $teacherUser = User::create([
            'name' => 'Test Teacher User',
            'email' => 'teacher.rbac@test.com',
            'user_type' => 'teacher',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($teacherUser)->get("http://{$domain}/admin/dashboard");
        $response->assertStatus(403);
    }

    /**
     * Test admin user can access admin dashboard.
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $adminUser = User::where('user_type', 'admin')->first();
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'Admin Principal',
                'email' => 'admin.rbac@test.com',
                'user_type' => 'admin',
                'password' => Hash::make('password'),
            ]);
        }

        $response = $this->actingAs($adminUser)->get("http://{$domain}/admin/dashboard");
        $response->assertStatus(200);
    }

    /**
     * Test librarian can access library catalogue but cannot access finance or payroll.
     */
    public function test_librarian_can_access_library_but_forbidden_from_finance_and_hr(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $librarianUser = User::create([
            'name' => 'Campus Librarian',
            'email' => 'librarian.rbac@test.com',
            'user_type' => 'librarian',
            'password' => Hash::make('password'),
        ]);

        // Can access library
        $libResponse = $this->actingAs($librarianUser)->get("http://{$domain}/admin/library");
        $libResponse->assertStatus(200);

        // Forbidden from finance reports
        $finResponse = $this->actingAs($librarianUser)->get("http://{$domain}/admin/finance-reports");
        $finResponse->assertStatus(403);

        // Forbidden from HR & Payroll
        $hrResponse = $this->actingAs($librarianUser)->get("http://{$domain}/admin/hr");
        $hrResponse->assertStatus(403);
    }

    /**
     * Test accountant can access finance reports but cannot access library or system user management.
     */
    public function test_accountant_can_access_finance_but_forbidden_from_library_and_users(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $accountantUser = User::create([
            'name' => 'Campus Accountant',
            'email' => 'accountant.rbac@test.com',
            'user_type' => 'accountant',
            'password' => Hash::make('password'),
        ]);

        // Can access finance
        $finResponse = $this->actingAs($accountantUser)->get("http://{$domain}/admin/finance-reports");
        $finResponse->assertStatus(200);

        // Forbidden from library
        $libResponse = $this->actingAs($accountantUser)->get("http://{$domain}/admin/library");
        $libResponse->assertStatus(403);

        // Forbidden from creating users
        $userResponse = $this->actingAs($accountantUser)->post("http://{$domain}/admin/users", [
            'name' => 'Hacker Staff',
            'email' => 'hacker@test.com',
            'user_type' => 'teacher',
        ]);
        $userResponse->assertStatus(403);
    }

    /**
     * Test active school tenant session cannot enter the Central Super Admin portal.
     */
    public function test_tenant_session_cannot_access_central_super_admin_dashboard(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $adminUser = User::where('user_type', 'admin')->first() ?? User::create([
            'name' => 'Campus Admin',
            'email' => 'campus.admin@test.com',
            'user_type' => 'admin',
            'password' => Hash::make('password'),
        ]);

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // When a tenant session is active, navigating to Central Super Admin must be strictly forbidden (403)
        $response = $this->withSession([
            'auth_context' => 'tenant',
            '_tenant_id' => $this->tenant->id,
        ])->actingAs($adminUser)->get(route('central.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * Test central super admin session does not automatically authenticate as tenant principal.
     */
    public function test_central_session_does_not_auto_authenticate_into_tenant_portal(): void
    {
        $domain = $this->tenant->domains->first()->domain;

        // Simulate central admin session hitting tenant domain
        $response = $this->withSession([
            'auth_context' => 'central',
            'is_super_admin' => true,
        ])->get("http://{$domain}/admin/dashboard");

        $response->assertRedirect("http://{$domain}/login");
    }
}
