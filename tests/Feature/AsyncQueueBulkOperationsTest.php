<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\FeeStructure;
use App\Jobs\GenerateMonthlyFeeChallansJob;
use App\Jobs\ImportStudentsJob;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

class AsyncQueueBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testqueuebulk' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Queue Bulk Campus',
            'admin_email' => 'admin@bulkqueue.test',
            'admin_password' => 'Secret@123',
        ]);

        tenancy()->initialize($this->tenant);
        $this->adminUser = User::where('user_type', 'admin')->first();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    /**
     * Test generate monthly challans controller dispatches queued job.
     */
    public function test_generate_monthly_challans_dispatches_queued_job(): void
    {
        Queue::fake();

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->adminUser)->post("http://{$domain}/admin/fees/generate-monthly", [
            'month_year' => '2026-09',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertSessionHas('success');
        Queue::assertPushed(GenerateMonthlyFeeChallansJob::class, function ($job) {
            return $job->monthYear === '2026-09';
        });
    }

    /**
     * Test GenerateMonthlyFeeChallansJob generates challans in tenant database.
     */
    public function test_generate_monthly_challans_job_executes_in_tenant_context(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 8', 'level' => 'middle', 'numeric_grade' => 8]);
        $section = Section::create(['class_id' => $class->id, 'name' => 'A']);

        FeeStructure::create([
            'class_id' => $class->id,
            'monthly_tuition' => 4500.00,
            'lab_charges' => 500.00,
            'transport_charges' => 0.00,
        ]);

        $studentUser = User::create([
            'name' => 'Bilal Ahmed',
            'email' => 'bilal@bulkqueue.test',
            'user_type' => 'student',
            'password' => bcrypt('password'),
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G8-001',
            'admission_number' => 'ADM-801',
            'status' => 'active',
        ]);

        $job = new GenerateMonthlyFeeChallansJob('2026-09', now()->toDateString(), now()->addDays(10)->toDateString());
        $count = $job->handle();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('fee_challans', [
            'student_id' => $student->id,
            'month_year' => '2026-09',
            'total_amount' => 5000.00,
            'status' => 'unpaid',
        ]);
    }

    /**
     * Test import students controller dispatches queued job.
     */
    public function test_import_students_dispatches_queued_job(): void
    {
        Queue::fake();

        $csvContent = "name,email,roll_number,class_name,section_name\nZainab Fatima,zainab@bulkqueue.test,105,Grade 9,A";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->adminUser)->post("http://{$domain}/admin/imports/students", [
            'csv_file' => $file,
        ]);

        $response->assertSessionHas('success');
        Queue::assertPushed(ImportStudentsJob::class, function ($job) {
            return count($job->rows) === 1 && $job->rows[0]['email'] === 'zainab@bulkqueue.test';
        });
    }

    /**
     * Test ImportStudentsJob imports student records in tenant database.
     */
    public function test_import_students_job_executes_in_tenant_context(): void
    {
        $rows = [
            [
                'name' => 'Hamza Tariq',
                'email' => 'hamza@bulkqueue.test',
                'roll_number' => '109',
                'class_name' => 'Grade 7',
                'section_name' => 'B',
            ]
        ];

        $job = new ImportStudentsJob($rows);
        $count = $job->handle();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('users', [
            'email' => 'hamza@bulkqueue.test',
            'user_type' => 'student',
        ]);
        $this->assertDatabaseHas('students', [
            'roll_number' => '109',
        ]);
    }
}
