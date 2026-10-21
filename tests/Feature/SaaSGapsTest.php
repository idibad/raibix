<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coupon;
use App\Models\SaaSSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaaSGapsTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create SaaS settings row
        SaaSSetting::create([
            'platform_name' => 'LMS Cloud Pakistan',
            'contact_email' => 'admin@lmscloud.pk',
            'support_phone' => '+923001234567',
            'maintenance_mode' => false,
            'public_onboarding' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Central Super Admin',
            'email' => 'testadmin@lms.com',
            'password' => bcrypt('testadmin'),
        ]);
    }

    /**
     * Test that maintenance mode blocks guests/normal users but allows superadmins.
     */
    public function test_maintenance_mode_blocks_requests(): void
    {
        // 1. Initially, maintenance is off. Guest request should return 200/302 (redirect) depending on route, but NOT 503.
        $response = $this->get('/');
        $response->assertStatus(200);

        // 2. Turn maintenance mode ON
        $setting = SaaSSetting::first();
        $setting->update(['maintenance_mode' => true]);

        // 3. Guest request should now receive a 503 Service Unavailable
        $response = $this->get('/');
        $response->assertStatus(503);

        // 4. Authenticated superadmin should still be allowed to access super-admin dashboard
        $response = $this->actingAs($this->adminUser)->get('/super-admin');
        $response->assertStatus(200);
    }

    /**
     * Test that super admin can create and delete coupons.
     */
    public function test_coupon_creation_and_deletion(): void
    {
        // 1. Create Coupon via API
        $response = $this->actingAs($this->adminUser)->post('/super-admin/coupons', [
            'code' => 'WINTER50',
            'discount_percentage' => 50,
            'max_uses' => 10,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('coupons', [
            'code' => 'WINTER50',
            'discount_percentage' => 50,
            'max_uses' => 10,
        ]);

        $coupon = Coupon::where('code', 'WINTER50')->first();
        $this->assertNotNull($coupon);

        // 2. Delete Coupon via API
        $response = $this->actingAs($this->adminUser)->delete("/super-admin/coupons/{$coupon->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id,
        ]);
    }
}
