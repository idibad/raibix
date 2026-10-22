<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Middleware\LogTenantRequests;
use App\Services\NotificationService;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LogSanitizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testlog' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Log Campus',
            'admin_email' => 'admin@log.test',
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
     * Test LogTenantRequests middleware never records raw active session IDs.
     */
    public function test_log_tenant_requests_does_not_log_session_id(): void
    {
        Log::spy();

        $request = Request::create('/admin/dashboard', 'GET');
        $session = app('session')->driver();
        $session->setId('secret_session_token_xyz_998877');
        $request->setLaravelSession($session);

        $middleware = new LogTenantRequests();
        $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) {
                // Assert raw session ID is NOT in the log string
                $noRawSession = !str_contains($message, 'secret_session_token_xyz_998877');
                // Assert HasSession indicator is present instead
                $hasSessionIndicator = str_contains($message, 'HasSession: YES');
                return $noRawSession && $hasSessionIndicator;
            });
    }

    /**
     * Test NotificationService phone number masking.
     */
    public function test_notification_service_masks_phone_number(): void
    {
        $service = new NotificationService();
        $masked = $service->maskPhoneNumber('03001234567');

        $this->assertStringNotContainsString('12345', $masked);
        $this->assertStringContainsString('*', $masked);
        $this->assertStringStartsWith('0300', $masked);
        $this->assertStringEndsWith('67', $masked);
    }

    /**
     * Test NotificationService message sanitization strips OTPs and PINs.
     */
    public function test_notification_service_sanitizes_message(): void
    {
        $service = new NotificationService();
        $sanitized = $service->sanitizeMessageForLogging('Your login verification code is 492810.');

        $this->assertStringNotContainsString('492810', $sanitized);
        $this->assertStringContainsString('****', $sanitized);
    }

    /**
     * Test NotificationService sendSms logs masked phone and masked payload.
     */
    public function test_send_sms_logs_masked_data(): void
    {
        Log::spy();

        $service = new NotificationService();
        $service->sendSms('03219876543', 'Your verification code is 654321', 'otp');

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) {
                $noRawPhone = !str_contains($message, '03219876543');
                $noRawOtp = !str_contains($message, '654321');
                $hasMask = str_contains($message, '****');
                return $noRawPhone && $noRawOtp && $hasMask;
            });
    }
}
