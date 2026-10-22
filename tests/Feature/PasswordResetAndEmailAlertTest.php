<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Mail\SchoolAlertMail;
use App\Models\Attendance;
use App\Models\ParentsProfile;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetAndEmailAlertTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $id = 'testreset' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Reset Campus',
            'admin_email' => 'admin@reset.test',
            'admin_password' => 'Secret@123',
        ]);
    }

    protected function tearDown(): void
    {
        $tenantId = $this->tenant?->id;

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if ($this->tenant) {
            $this->tenant->delete();
        }

        if ($tenantId) {
            $testFile = database_path("tenant{$tenantId}");
            if (File::exists($testFile)) {
                File::delete($testFile);
            }
        }

        parent::tearDown();
    }

    public function test_orphan_cleaner_command_detects_and_deletes_orphan_databases(): void
    {
        $tempDir = storage_path('framework/testing/orphan_test_' . uniqid());
        File::makeDirectory($tempDir, 0755, true, true);

        $dummyOrphan = $tempDir . DIRECTORY_SEPARATOR . 'tenantdummyorphanforcleanup';
        File::put($dummyOrphan, 'dummy-content');

        $validTenantFile = $tempDir . DIRECTORY_SEPARATOR . "tenant{$this->tenant->id}";
        File::put($validTenantFile, 'valid-tenant-content');

        $this->assertFileExists($dummyOrphan);
        $this->assertFileExists($validTenantFile);

        $this->artisan('tenants:clean-orphans', [
            '--path' => $tempDir,
            '--force' => true,
        ])->assertExitCode(0);

        // Orphan must be deleted, registered tenant database must be preserved
        $this->assertFileDoesNotExist($dummyOrphan);
        $this->assertFileExists($validTenantFile);

        File::deleteDirectory($tempDir);
    }

    public function test_tenant_forgot_password_dispatches_reset_email_with_noreply_address(): void
    {
        tenancy()->initialize($this->tenant);
        $domain = $this->tenant->domains()->first()->domain;

        $user = User::create([
            'name' => 'Teacher Reset',
            'email' => 'teacher.reset@school.test',
            'password' => Hash::make('Secret@123'),
            'user_type' => 'teacher',
            'status' => 'active',
        ]);

        $response = $this->post("http://{$domain}/forgot-password", [
            'email' => 'teacher.reset@school.test',
        ]);

        $response->assertSessionHas('status');

        // Verify token in database
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', 'teacher.reset@school.test')
            ->first();

        $this->assertNotNull($tokenRecord);
        $this->assertNotEmpty($tokenRecord->token);

        // Verify mail sent from noreply@raibix.com
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) {
            return $mail->hasTo('teacher.reset@school.test') &&
                   $mail->hasFrom('noreply@raibix.com');
        });
    }

    public function test_tenant_password_reset_form_submits_and_updates_credentials(): void
    {
        tenancy()->initialize($this->tenant);
        $domain = $this->tenant->domains()->first()->domain;

        $user = User::create([
            'name' => 'Principal Reset',
            'email' => 'principal.reset@school.test',
            'password' => Hash::make('OldPassword123!'),
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'principal.reset@school.test'],
            [
                'token' => Hash::make($rawToken),
                'created_at' => now(),
            ]
        );

        $response = $this->post("http://{$domain}/reset-password", [
            'token' => $rawToken,
            'email' => 'principal.reset@school.test',
            'password' => 'NewPassword2026!',
            'password_confirmation' => 'NewPassword2026!',
        ]);

        $response->assertRedirect(route('tenant.login'));
        $response->assertSessionHas('success');

        // Token should be consumed
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'principal.reset@school.test',
        ]);

        // User password should be updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword2026!', $user->password));
    }

    public function test_expired_password_reset_token_is_rejected(): void
    {
        tenancy()->initialize($this->tenant);
        $domain = $this->tenant->domains()->first()->domain;

        $user = User::create([
            'name' => 'Expired User',
            'email' => 'expired.token@school.test',
            'password' => Hash::make('Password123!'),
            'user_type' => 'teacher',
            'status' => 'active',
        ]);

        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'expired.token@school.test'],
            [
                'token' => Hash::make($rawToken),
                'created_at' => Carbon::now()->subMinutes(65), // Expired past 60 min TTL
            ]
        );

        $response = $this->from("http://{$domain}/reset-password/" . $rawToken)
            ->post("http://{$domain}/reset-password", [
                'token' => $rawToken,
                'email' => 'expired.token@school.test',
                'password' => 'NewSecurePassword!',
                'password_confirmation' => 'NewSecurePassword!',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_triggered_password_reset_creates_token_and_sends_email(): void
    {
        tenancy()->initialize($this->tenant);
        $domain = $this->tenant->domains()->first()->domain;

        $admin = User::where('user_type', 'admin')->first() ?? User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@school.test',
            'password' => Hash::make('Secret@123'),
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        $targetStaff = User::create([
            'name' => 'Staff Target',
            'email' => 'staff.target@school.test',
            'password' => Hash::make('Secret@123'),
            'user_type' => 'teacher',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post("http://{$domain}/admin/users/{$targetStaff->id}/reset-password");

        $response->assertSessionHas('success');

        // Token created
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'staff.target@school.test',
        ]);

        // Mail sent from noreply@raibix.com
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) {
            return $mail->hasTo('staff.target@school.test') &&
                   $mail->hasFrom('noreply@raibix.com');
        });
    }

    public function test_absence_alert_sends_email_to_parent_from_noreply_address(): void
    {
        tenancy()->initialize($this->tenant);

        $parentUser = User::create([
            'name' => 'Parent User',
            'email' => 'parent.alert@school.test',
            'phone' => '03001234567',
            'password' => Hash::make('Secret@123'),
            'user_type' => 'parent',
            'status' => 'active',
        ]);

        $parentProfile = ParentsProfile::create([
            'user_id' => $parentUser->id,
            'father_name' => 'Rashid Khan',
            'father_phone' => '03001234567',
            'mother_name' => 'Fatima Khan',
            'address' => 'Test Address',
        ]);

        $studentUser = User::create([
            'name' => 'Taimur Khan',
            'email' => 'taimur@school.test',
            'password' => Hash::make('Secret@123'),
            'user_type' => 'student',
            'status' => 'active',
        ]);

        $schoolClass = SchoolClass::firstOrCreate(['name' => 'Grade 8'], ['level' => 'middle', 'numeric_grade' => 8]);
        $section = Section::firstOrCreate(['class_id' => $schoolClass->id, 'name' => 'Green']);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'parent_id' => $parentProfile->id,
            'admission_number' => 'ADM-TEST-001',
            'current_class_id' => $schoolClass->id,
            'current_section_id' => $section->id,
            'roll_number' => '8-G-12',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'date' => now(),
            'status' => 'absent',
        ]);

        $notificationService = app(NotificationService::class);
        $result = $notificationService->sendAbsenceAlert($student, $attendance);

        $this->assertTrue($result);

        Mail::assertSent(SchoolAlertMail::class, function (SchoolAlertMail $mail) {
            return $mail->hasTo('parent.alert@school.test') &&
                   $mail->hasFrom('noreply@raibix.com') &&
                   $mail->alertType === 'danger';
        });
    }
}
