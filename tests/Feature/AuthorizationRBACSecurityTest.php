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
}
