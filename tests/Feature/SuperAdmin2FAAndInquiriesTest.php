<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PlatformInquiry;
use App\Models\SaaSSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuperAdmin2FAAndInquiriesTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        SaaSSetting::create([
            'platform_name' => 'Raibix',
            'contact_email' => 'admin@raibix.com',
            'support_phone' => '+1 (555) 019-2834',
            'maintenance_mode' => false,
            'public_onboarding' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@raibix.com',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_public_inquiry_can_be_submitted_from_portal(): void
    {
        $response = $this->post('/inquiries', [
            'name' => 'Dr. Ayesha Khan',
            'email' => 'ayesha@beaconhouse.edu',
            'phone' => '+923001234567',
            'institution' => 'Beaconhouse System',
            'message' => 'We are interested in adopting Raibix for 3 school campuses.',
            'type' => 'contact',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('platform_inquiries', [
            'name' => 'Dr. Ayesha Khan',
            'email' => 'ayesha@beaconhouse.edu',
            'institution' => 'Beaconhouse System',
            'type' => 'contact',
            'status' => 'new',
        ]);
    }

    public function test_expert_call_inquiry_can_be_submitted_from_modal(): void
    {
        $response = $this->post('/inquiries', [
            'name' => 'Omar Farooq',
            'email' => 'omar@cityschool.pk',
            'phone' => '+923219876543',
            'institution' => 'The City School',
            'message' => 'Requesting an urgent tech demo for fee collection workflows.',
            'type' => 'expert_call',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('platform_inquiries', [
            'name' => 'Omar Farooq',
            'email' => 'omar@cityschool.pk',
            'type' => 'expert_call',
            'status' => 'new',
        ]);
    }

    public function test_super_admin_can_update_inquiry_status_and_delete(): void
    {
        $inquiry = PlatformInquiry::create([
            'name' => 'Tariq Mahmood',
            'email' => 'tariq@roots.edu',
            'phone' => '+923335551234',
            'institution' => 'Roots Millennium',
            'message' => 'Need pricing details.',
            'type' => 'contact',
            'status' => 'new',
        ]);

        // Status update
        $response = $this->actingAs($this->adminUser)->post("/super-admin/inquiries/{$inquiry->id}/status", [
            'status' => 'contacted',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('contacted', $inquiry->fresh()->status);

        // Delete inquiry
        $deleteResponse = $this->actingAs($this->adminUser)->delete("/super-admin/inquiries/{$inquiry->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('platform_inquiries', ['id' => $inquiry->id]);
    }

    public function test_super_admin_login_triggers_2fa_challenge_and_verifies_otp(): void
    {
        // Step 1: Submit valid credentials
        $response = $this->post('/super-admin/login', [
            'email' => 'superadmin@raibix.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/super-admin/login');
        $response->assertSessionHas('requires_2fa', true);
        $response->assertSessionHas('2fa_user_id', $this->adminUser->id);
        $response->assertSessionHas('2fa_code');
        
        $otp = session('2fa_code');
        $this->assertNotEmpty($otp);
        $this->assertEquals(6, strlen($otp));
        $this->assertGuest(); // User not logged in yet

        // Step 2: Attempt verify with invalid code
        $failResponse = $this->post('/super-admin/login/verify-2fa', [
            'code' => '000000',
        ]);
        $failResponse->assertSessionHasErrors(['code']);
        $this->assertGuest();

        // Step 3: Verify with correct OTP
        $verifyResponse = $this->post('/super-admin/login/verify-2fa', [
            'code' => $otp,
        ]);
        $verifyResponse->assertRedirect('/super-admin');
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_super_admin_2fa_can_be_cancelled(): void
    {
        // Init 2FA
        $response = $this->post('/super-admin/login', [
            'email' => 'superadmin@raibix.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHas('2fa_user_id', $this->adminUser->id);

        // Cancel
        $cancelResponse = $this->post('/super-admin/login/cancel-2fa');
        $cancelResponse->assertRedirect('/super-admin/login');
        $cancelResponse->assertSessionMissing('2fa_user_id');
        $cancelResponse->assertSessionMissing('2fa_code');
        $this->assertGuest();
    }
}
