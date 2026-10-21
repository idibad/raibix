<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\ParentsProfile;
use App\Models\FeeChallan;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ChallanScopingIDORTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $parentUserA;
    protected $studentUserA;
    protected $challanA;

    protected $parentUserB;
    protected $studentUserB;
    protected $challanB;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testschoolidor' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test IDOR Scoping Campus',
            'admin_email' => 'admin@idor.test',
            'admin_password' => 'Secret@123',
        ]);

        tenancy()->initialize($this->tenant);

        $class = SchoolClass::create(['name' => 'Grade 9', 'level' => 'matric', 'numeric_grade' => 9]);
        $section = Section::create(['class_id' => $class->id, 'name' => 'A']);

        // Admin User
        $this->adminUser = User::where('user_type', 'admin')->first();

        // Family A
        $this->parentUserA = User::create([
            'name' => 'Parent A',
            'email' => 'parentA@idor.test',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);
        $parentProfileA = ParentsProfile::create([
            'user_id' => $this->parentUserA->id,
            'father_name' => 'Father A',
        ]);

        $this->studentUserA = User::create([
            'name' => 'Student A',
            'email' => 'studentA@idor.test',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);
        $studentA = Student::create([
            'user_id' => $this->studentUserA->id,
            'parent_id' => $parentProfileA->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G9-001',
            'admission_number' => 'ADM-101',
            'status' => 'Active',
        ]);
        $this->challanA = FeeChallan::create([
            'student_id' => $studentA->id,
            'challan_number' => 'CHL-A001',
            'month_year' => '2026-08',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total_amount' => 5000.00,
            'status' => 'unpaid',
        ]);

        // Family B
        $this->parentUserB = User::create([
            'name' => 'Parent B',
            'email' => 'parentB@idor.test',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);
        $parentProfileB = ParentsProfile::create([
            'user_id' => $this->parentUserB->id,
            'father_name' => 'Father B',
        ]);

        $this->studentUserB = User::create([
            'name' => 'Student B',
            'email' => 'studentB@idor.test',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);
        $studentB = Student::create([
            'user_id' => $this->studentUserB->id,
            'parent_id' => $parentProfileB->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G9-002',
            'admission_number' => 'ADM-102',
            'status' => 'Active',
        ]);
        $this->challanB = FeeChallan::create([
            'student_id' => $studentB->id,
            'challan_number' => 'CHL-B001',
            'month_year' => '2026-08',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total_amount' => 6000.00,
            'status' => 'unpaid',
        ]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    /**
     * Parent A can view and print their own child's challan.
     */
    public function test_parent_can_print_their_child_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->get("http://{$domain}/challans/{$this->challanA->id}/print");
        $response->assertStatus(200);
    }

    /**
     * Parent A cannot view or print Student B's challan (returns HTTP 403 Forbidden).
     */
    public function test_parent_cannot_print_other_student_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->get("http://{$domain}/challans/{$this->challanB->id}/print");
        $response->assertStatus(403);
    }

    /**
     * Parent A cannot pay Student B's challan (returns HTTP 403 Forbidden).
     */
    public function test_parent_cannot_pay_other_student_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->post("http://{$domain}/parent/challans/{$this->challanB->id}/pay", [
            'gateway' => 'jazzcash',
            'amount' => 6000.00,
            'mobile_number' => '03001234567',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Student A can view and print their own fee challan.
     */
    public function test_student_can_print_own_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->studentUserA)->get("http://{$domain}/challans/{$this->challanA->id}/print");
        $response->assertStatus(200);
    }

    /**
     * Student A cannot view or print Student B's fee challan (returns HTTP 403 Forbidden).
     */
    public function test_student_cannot_print_other_student_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->studentUserA)->get("http://{$domain}/challans/{$this->challanB->id}/print");
        $response->assertStatus(403);
    }

    /**
     * Student A cannot checkout or pay Student B's fee challan (returns HTTP 403 Forbidden).
     */
    public function test_student_cannot_checkout_other_student_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->studentUserA)->post("http://{$domain}/payments/checkout/{$this->challanB->id}", [
            'gateway' => 'stripe',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Admin can view and print any student's fee challan.
     */
    public function test_admin_can_print_any_student_challan(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $responseA = $this->actingAs($this->adminUser)->get("http://{$domain}/challans/{$this->challanA->id}/print");
        $responseA->assertStatus(200);

        $responseB = $this->actingAs($this->adminUser)->get("http://{$domain}/challans/{$this->challanB->id}/print");
        $responseB->assertStatus(200);
    }
}
