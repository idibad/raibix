<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a tenant can be provisioned successfully.
     */
    public function test_tenant_can_be_created_and_provisioned(): void
    {
        $id = 'testschoolportal' . uniqid();
        $tenant = Tenant::create([
            'id' => $id,
            'name' => 'Test School Portal Campus',
        ]);

        $tenant->domains()->create([
            'domain' => $id . '.localhost',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $id,
            'name' => 'Test School Portal Campus',
        ]);

        // Initialize tenant context
        tenancy()->initialize($tenant);
        $this->assertTrue(tenancy()->initialized);

        // Revert tenant context
        tenancy()->end();
        $this->assertFalse(tenancy()->initialized);
    }

    /**
     * Test that payment gateway configuration can be saved correctly.
     */
    public function test_gateway_configuration_can_be_saved(): void
    {
        $id = 'testschoolportal' . uniqid();
        $tenant = Tenant::create([
            'id' => $id,
            'name' => 'Test School Portal Campus',
        ]);
        $tenant->domains()->create([
            'domain' => $id . '.localhost',
        ]);

        tenancy()->initialize($tenant);

        $user = \App\Models\User::create([
            'name' => 'Principal Admin',
            'email' => 'principal@test.lms.cloud',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
        ]);

        $response = $this->actingAs($user)->post("http://{$id}.localhost/admin/settings/gateways", [
            'stripe_public_key' => 'pk_test_123',
            'stripe_secret_key' => 'sk_test_456',
            'jazzcash_merchant_id' => 'jc_789',
            'jazzcash_password' => 'jc_pass',
            'jazzcash_hash' => 'jc_hash',
        ]);

        $response->assertRedirect();
        
        $updatedTenant = Tenant::find($id);
        $this->assertEquals('pk_test_123', $updatedTenant->stripe_public_key);
        $this->assertEquals('sk_test_456', $updatedTenant->stripe_secret_key);
        $this->assertEquals('jc_789', $updatedTenant->jazzcash_merchant_id);
        $this->assertEquals('jc_pass', $updatedTenant->jazzcash_password);
        $this->assertEquals('jc_hash', $updatedTenant->jazzcash_hash);

        tenancy()->end();
    }

    /**
     * Test that tenant provisioning strictly creates only the requested admin and NO dummy users.
     */
    public function test_provision_school_does_not_create_dummy_users(): void
    {
        $id = 'clean' . uniqid();
        $provisioningService = app(\App\Services\TenantProvisioningService::class);
        $tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Clean Slate Academy',
            'admin_name' => 'Principal Farhan',
            'admin_email' => "principal.farhan@{$id}.com",
            'admin_phone' => '03009998877',
            'admin_password' => 'SecurePass123!',
        ]);

        tenancy()->initialize($tenant);

        // Verify only the designated principal user exists
        $users = \App\Models\User::all();
        $this->assertCount(1, $users, 'Tenant should only have 1 designated school admin user, no dummy users.');
        $this->assertEquals("principal.farhan@{$id}.com", $users->first()->email);
        $this->assertEquals('Principal Farhan', $users->first()->name);
        $this->assertEquals('admin', $users->first()->user_type);

        // Verify no dummy students, parents, or teachers exist
        $this->assertEquals(0, \App\Models\Student::count(), 'Zero dummy students should be created.');
        $this->assertEquals(0, \App\Models\ParentsProfile::count(), 'Zero dummy parents should be created.');
        $this->assertEquals(0, \App\Models\User::where('user_type', '!=', 'admin')->count(), 'Zero non-admin dummy users.');

        tenancy()->end();
    }
}
