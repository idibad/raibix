<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\FeeChallan;
use App\Jobs\SendSmsJob;
use App\Jobs\SendWhatsappJob;
use App\Services\NotificationService;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class AsyncNotificationsQueueTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $adminUser;
    protected $challan;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testqueuenotif' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Queue Notifications Campus',
            'admin_email' => 'admin@notifqueue.test',
            'admin_password' => 'Secret@123',
        ]);

        tenancy()->initialize($this->tenant);
        $this->adminUser = User::where('user_type', 'admin')->first();

        $class = SchoolClass::create(['name' => 'Grade 10', 'level' => 'matric', 'numeric_grade' => 10]);
        $section = Section::create(['class_id' => $class->id, 'name' => 'A']);

        $studentUser = User::create([
            'name' => 'Usman Ghani',
            'email' => 'usman@notifqueue.test',
            'phone' => '03009988776',
            'user_type' => 'student',
            'password' => bcrypt('password'),
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-099',
            'admission_number' => 'ADM-999',
            'status' => 'active',
        ]);

        $this->challan = FeeChallan::create([
            'challan_number' => 'CHL-NOTIF-01',
            'student_id' => $student->id,
            'fee_type' => 'monthly',
            'month_year' => '2026-08',
            'issue_date' => now()->subDays(15)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'tuition_fee' => 6000.00,
            'total_amount' => 6000.00,
            'paid_amount' => 0.00,
            'status' => 'overdue',
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
     * Test defaulter reminder controller dispatches SendSmsJob and SendWhatsappJob.
     */
    public function test_send_defaulter_reminder_dispatches_queued_jobs(): void
    {
        Queue::fake();

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->adminUser)->post("http://{$domain}/admin/defaulters/{$this->challan->id}/remind");

        $response->assertSessionHas('success');
        Queue::assertPushed(SendSmsJob::class, function ($job) {
            return $job->phone === '03009988776' && $job->eventType === 'fee_challan';
        });
        Queue::assertPushed(SendWhatsappJob::class, function ($job) {
            return $job->phone === '03009988776' && $job->eventType === 'fee_challan';
        });
    }

    /**
     * Test SendSmsJob executes and inserts log in tenant database.
     */
    public function test_send_sms_job_executes_in_tenant_context(): void
    {
        $job = new SendSmsJob('03001112233', 'Test SMS Message', 'fee_challan');
        $log = $job->handle(app(NotificationService::class));

        $this->assertNotNull($log->id);
        $this->assertDatabaseHas('sms_logs', [
            'recipient_phone' => '03001112233',
            'event_type' => 'fee_challan',
        ]);
    }

    /**
     * Test SendWhatsappJob executes and inserts log in tenant database.
     */
    public function test_send_whatsapp_job_executes_in_tenant_context(): void
    {
        $job = new SendWhatsappJob('03001112233', 'Test WhatsApp Message', 'fee_challan', 'fee_challan_overdue');
        $log = $job->handle(app(NotificationService::class));

        $this->assertNotNull($log->id);
        $this->assertDatabaseHas('whatsapp_logs', [
            'recipient_phone' => '03001112233',
            'event_type' => 'fee_challan',
            'template_name' => 'fee_challan_overdue',
        ]);
    }
}
