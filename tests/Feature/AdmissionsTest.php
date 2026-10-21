<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\AdmissionInquiry;
use App\Models\AdmissionApplication;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\FeeChallan;
use App\Services\AdmissionService;
use App\Services\FeeService;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdmissionsTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testschooladmissions' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Admissions Campus',
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
     * Test admissions inquiry creation and status update.
     */
    public function test_admissions_inquiry_creation_and_status_update(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 8', 'level' => 'matric', 'numeric_grade' => 8]);

        $inquiry = AdmissionInquiry::create([
            'inquiry_code' => 'INQ-1002',
            'student_name' => 'Kamran Khan',
            'parent_name' => 'Khan Mohammad',
            'parent_phone' => '03001234567',
            'parent_email' => 'kamran@example.com',
            'desired_class_id' => $class->id,
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('admission_inquiries', [
            'student_name' => 'Kamran Khan',
            'status' => 'new',
        ]);

        // Simulate status change to Qualified
        $inquiry->update(['status' => 'qualified']);

        $this->assertDatabaseHas('admission_inquiries', [
            'id' => $inquiry->id,
            'status' => 'qualified',
        ]);
    }

    /**
     * Test the entire admissions pipeline from Inquiry -> Application -> Merit Decision -> Fee Payment -> Student Enrollment.
     */
    public function test_full_admissions_pipeline(): void
    {
        // 1. Create a class and section
        $class = SchoolClass::create(['name' => 'Grade 8', 'level' => 'matric', 'numeric_grade' => 8]);
        \App\Models\Section::create(['class_id' => $class->id, 'name' => 'A']);

        // 2. Create Inquiry
        $service = app(AdmissionService::class);
        $inquiry = $service->createInquiry([
            'student_name' => 'Ahmad Khan',
            'desired_class_id' => $class->id,
            'parent_name' => 'Muhammad Khan',
            'parent_phone' => '03001234567',
            'parent_email' => 'ahmad@example.com',
        ]);

        $this->assertDatabaseHas('admission_inquiries', [
            'id' => $inquiry->id,
            'student_name' => 'Ahmad Khan',
            'status' => 'new',
        ]);

        // 3. Submit Application
        $application = $service->createApplication([
            'inquiry_id' => $inquiry->id,
            'student_name' => 'Ahmad Khan',
            'class_id' => $class->id,
            'father_name' => 'Muhammad Khan',
            'father_phone' => '03001234567',
        ]);

        $this->assertDatabaseHas('admission_applications', [
            'id' => $application->id,
            'student_name' => 'Ahmad Khan',
            'admission_status' => 'applied',
        ]);

        // 4. Decide Merit (Approved)
        $meritStatus = $service->decideMerit($application, 85.0, 100.0);
        $this->assertEquals('approved', $meritStatus);

        $this->assertDatabaseHas('admission_applications', [
            'id' => $application->id,
            'admission_status' => 'fee_pending',
        ]);

        // Verify Admission Challan generated
        $challan = FeeChallan::where('fee_type', 'admission')
            ->where('status', 'unpaid')
            ->first();
        $this->assertNotNull($challan);

        // 5. Process Payment and Enroll Student
        $feeService = app(FeeService::class);
        $feeService->processPayment($challan, 'cash', $challan->total_amount, [
            'reference_no' => 'MOCK-PAYMENT-123',
            'tid' => 'MOCK-PAYMENT-123'
        ]);

        // Verify challan is paid
        $this->assertEquals('paid', $challan->fresh()->status);

        // Verify student is enrolled
        $this->assertDatabaseHas('students', [
            'status' => 'active',
            'current_class_id' => $class->id,
        ]);

        // Verify student and parent user accounts exist
        $this->assertDatabaseHas('users', [
            'name' => 'Ahmad Khan',
            'user_type' => 'student',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Muhammad Khan',
            'user_type' => 'parent',
        ]);
    }

    /**
     * Test that re-evaluating merit does not generate duplicate challans or throw duplicate key error.
     */
    public function test_duplicate_challan_generation_returns_existing_challan(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 8', 'level' => 'matric', 'numeric_grade' => 8]);
        $service = app(AdmissionService::class);
        $inquiry = $service->createInquiry([
            'student_name' => 'John Doe',
            'desired_class_id' => $class->id,
            'parent_name' => 'John Doe Sr.',
            'parent_phone' => '03001111111',
        ]);
        $application = $service->createApplication([
            'inquiry_id' => $inquiry->id,
            'student_name' => 'John Doe',
            'class_id' => $class->id,
            'father_name' => 'John Doe Sr.',
            'father_phone' => '03001111111',
        ]);

        // First merit decision -> generates challan
        $status1 = $service->decideMerit($application, 90.0);
        $this->assertEquals('approved', $status1);

        $challanCountBefore = FeeChallan::count();

        // Second merit decision (simulate double click / retry)
        $status2 = $service->decideMerit($application, 90.0);
        $this->assertEquals('approved', $status2);

        // Verify no duplicate challan was created
        $this->assertEquals($challanCountBefore, FeeChallan::count());
    }

    /**
     * Test that enrolling an already enrolled application throws an exception.
     */
    public function test_cannot_enroll_already_enrolled_student(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 8', 'level' => 'matric', 'numeric_grade' => 8]);
        \App\Models\Section::create(['class_id' => $class->id, 'name' => 'A']);
        
        $service = app(AdmissionService::class);
        $inquiry = $service->createInquiry([
            'student_name' => 'Jane Doe',
            'desired_class_id' => $class->id,
            'parent_name' => 'Jane Doe Sr.',
            'parent_phone' => '03002222222',
        ]);
        $application = $service->createApplication([
            'inquiry_id' => $inquiry->id,
            'student_name' => 'Jane Doe',
            'class_id' => $class->id,
            'father_name' => 'Jane Doe Sr.',
            'father_phone' => '03002222222',
        ]);

        $service->decideMerit($application, 95.0);
        $challan = FeeChallan::where('fee_type', 'admission')->first();

        $feeService = app(FeeService::class);
        $feeService->processPayment($challan, 'cash', $challan->total_amount, [
            'reference_no' => 'MOCK-PAYMENT-456',
            'tid' => 'MOCK-PAYMENT-456'
        ]);

        $this->assertEquals('enrolled', $application->fresh()->admission_status);

        // Try enrolling again and assert it throws exception
        $this->expectException(\Exception::class);
        $service->enrollStudent($application->fresh());
    }
}

