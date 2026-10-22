<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\ParentsProfile;
use App\Models\StudentDocument;
use App\Models\FeeChallan;
use App\Models\FeeTransaction;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SecureStorageIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $parentUserA;
    protected $studentUserA;
    protected $studentA;
    protected $documentA;
    protected $challanA;
    protected $transactionA;

    protected $parentUserB;
    protected $studentUserB;
    protected $studentB;
    protected $documentB;
    protected $challanB;
    protected $transactionB;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $id = 'testsecurestorage' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Secure Storage Campus',
            'admin_email' => 'admin@storage.test',
            'admin_password' => 'Secret@123',
        ]);

        tenancy()->initialize($this->tenant);
        $this->adminUser = User::where('user_type', 'admin')->first();

        $class = SchoolClass::create(['name' => 'Grade 10', 'level' => 'matric', 'numeric_grade' => 10]);
        $section = Section::create(['class_id' => $class->id, 'name' => 'A']);

        // Family A
        $this->parentUserA = User::create([
            'name' => 'Parent A',
            'email' => 'parentA@storage.test',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);
        $parentProfileA = ParentsProfile::create([
            'user_id' => $this->parentUserA->id,
            'father_name' => 'Father A',
        ]);

        $this->studentUserA = User::create([
            'name' => 'Student A',
            'email' => 'studentA@storage.test',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);
        $this->studentA = Student::create([
            'user_id' => $this->studentUserA->id,
            'parent_id' => $parentProfileA->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-001',
            'admission_number' => 'ADM-301',
            'status' => 'Active',
        ]);

        // Create file in local storage for Student A
        $pathA = "tenants/{$this->tenant->id}/students/{$this->studentA->id}/docs/bform.pdf";
        Storage::disk('local')->put($pathA, 'Student A Birth Certificate Content');
        $this->documentA = StudentDocument::create([
            'student_id' => $this->studentA->id,
            'title' => 'B-Form',
            'document_type' => 'b_form',
            'file_url' => $pathA,
            'uploaded_by_user_id' => $this->adminUser->id,
        ]);

        $this->challanA = FeeChallan::create([
            'student_id' => $this->studentA->id,
            'challan_number' => 'CHL-SEC-A',
            'month_year' => '2026-08',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total_amount' => 5000.00,
            'status' => 'unpaid',
        ]);

        $receiptPathA = "fees/receipts/{$this->challanA->id}/receipt_a.jpg";
        Storage::disk('local')->put($receiptPathA, 'Bank slip image content A');
        $this->transactionA = $this->challanA->transactions()->create([
            'gateway' => 'Bank Transfer / Manual Upload',
            'amount' => 5000.00,
            'payment_date' => now(),
            'transaction_reference' => 'TXN-A-001',
            'receipt_path' => $receiptPathA,
            'status' => 'pending',
        ]);

        // Family B
        $this->parentUserB = User::create([
            'name' => 'Parent B',
            'email' => 'parentB@storage.test',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);
        $parentProfileB = ParentsProfile::create([
            'user_id' => $this->parentUserB->id,
            'father_name' => 'Father B',
        ]);

        $this->studentUserB = User::create([
            'name' => 'Student B',
            'email' => 'studentB@storage.test',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);
        $this->studentB = Student::create([
            'user_id' => $this->studentUserB->id,
            'parent_id' => $parentProfileB->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-002',
            'admission_number' => 'ADM-302',
            'status' => 'Active',
        ]);

        $pathB = "tenants/{$this->tenant->id}/students/{$this->studentB->id}/docs/cnic.pdf";
        Storage::disk('local')->put($pathB, 'Student B CNIC Content');
        $this->documentB = StudentDocument::create([
            'student_id' => $this->studentB->id,
            'title' => 'Father CNIC',
            'document_type' => 'cnic',
            'file_url' => $pathB,
            'uploaded_by_user_id' => $this->adminUser->id,
        ]);

        $this->challanB = FeeChallan::create([
            'student_id' => $this->studentB->id,
            'challan_number' => 'CHL-SEC-B',
            'month_year' => '2026-08',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total_amount' => 6000.00,
            'status' => 'unpaid',
        ]);

        $receiptPathB = "fees/receipts/{$this->challanB->id}/receipt_b.jpg";
        Storage::disk('local')->put($receiptPathB, 'Bank slip image content B');
        $this->transactionB = $this->challanB->transactions()->create([
            'gateway' => 'Bank Transfer / Manual Upload',
            'amount' => 6000.00,
            'payment_date' => now(),
            'transaction_reference' => 'TXN-B-001',
            'receipt_path' => $receiptPathB,
            'status' => 'pending',
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
     * Parent A can download their own child's document.
     */
    public function test_parent_can_download_own_child_document(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->get("http://{$domain}/students/{$this->studentA->id}/documents/{$this->documentA->id}/download");

        $response->assertStatus(200);
    }

    /**
     * Parent A cannot download Student B's document (returns HTTP 403 Forbidden).
     */
    public function test_parent_cannot_download_other_student_document(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->get("http://{$domain}/students/{$this->studentB->id}/documents/{$this->documentB->id}/download");

        $response->assertStatus(403);
    }

    /**
     * Parent A can download payment receipt of their own child's fee challan.
     */
    public function test_parent_can_download_child_fee_receipt(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->get("http://{$domain}/fees/receipts/{$this->transactionA->id}/download");

        $response->assertStatus(200);
    }

    /**
     * Parent A cannot download payment receipt of another student's fee challan (returns HTTP 403 Forbidden).
     */
    public function test_parent_cannot_download_other_student_fee_receipt(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->get("http://{$domain}/fees/receipts/{$this->transactionB->id}/download");

        $response->assertStatus(403);
    }

    /**
     * Admin can download documents for any student.
     */
    public function test_admin_can_download_any_student_document(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $responseA = $this->actingAs($this->adminUser)->get("http://{$domain}/students/{$this->studentA->id}/documents/{$this->documentA->id}/download");
        $responseA->assertStatus(200);

        $responseB = $this->actingAs($this->adminUser)->get("http://{$domain}/students/{$this->studentB->id}/documents/{$this->documentB->id}/download");
        $responseB->assertStatus(200);
    }

    /**
     * Upload document via StudentController uses local storage disk.
     */
    public function test_upload_document_stores_on_local_disk(): void
    {
        $file = UploadedFile::fake()->create('medical_cert.pdf', 500, 'application/pdf');

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->adminUser)->post("http://{$domain}/admin/students/{$this->studentA->id}/documents", [
            'title' => 'Medical Certificate',
            'document_type' => 'medical',
            'document_file' => $file,
        ]);

        $response->assertSessionHas('success');
        $doc = StudentDocument::where('student_id', $this->studentA->id)->where('title', 'Medical Certificate')->first();
        $this->assertNotNull($doc);
        $this->assertTrue(Storage::disk('local')->exists($doc->file_url));
    }
}
