<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantContactInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_contact_information_can_be_updated_and_retrieved(): void
    {
        $id = 'testschoolcontact' . uniqid();
        $domain = $id . '.localhost';

        $tenant = Tenant::create([
            'id' => $id,
            'name' => 'Apex Grammar School',
            'status' => 'active',
            'phone' => '+92 300 1112233',
            'email' => 'admissions@apexschool.edu.pk',
            'address' => 'Plot 42, Sector F-8/3, Islamabad',
        ]);

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        // 1. Verify tenant attributes
        $this->assertEquals('+92 300 1112233', $tenant->phone);
        $this->assertEquals('admissions@apexschool.edu.pk', $tenant->email);
        $this->assertEquals('Plot 42, Sector F-8/3, Islamabad', $tenant->address);

        // 2. Initialize tenant and check public landing page response
        tenancy()->initialize($tenant);

        $response = $this->get("http://{$domain}/");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Tenant/Landing')
                ->where('school.phone', '+92 300 1112233')
                ->where('school.email', 'admissions@apexschool.edu.pk')
                ->where('school.address', 'Plot 42, Sector F-8/3, Islamabad')
                ->where('tenant.phone', '+92 300 1112233')
                ->where('tenant.email', 'admissions@apexschool.edu.pk')
                ->where('tenant.address', 'Plot 42, Sector F-8/3, Islamabad')
        );

        // 3. Test updating contact info via AdminPortalController::updateBranding
        $admin = User::create([
            'name' => 'Principal Admin',
            'email' => 'principal@apexschool.edu.pk',
            'password' => bcrypt('Secret@123'),
            'user_type' => 'admin',
        ]);

        $updateResponse = $this->actingAs($admin)->post("http://{$domain}/admin/settings/branding", [
            'cover_color' => '#0F766E',
            'secondary_color' => '#134E4A',
            'accent_color' => '#2DD4BF',
            'phone' => '+92 51 9876543',
            'email' => 'principal@apexschool.edu.pk',
            'address' => 'New Campus Avenue, Phase 2, Islamabad',
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $tenant->refresh();
        $this->assertEquals('+92 51 9876543', $tenant->phone);
        $this->assertEquals('principal@apexschool.edu.pk', $tenant->email);
        $this->assertEquals('New Campus Avenue, Phase 2, Islamabad', $tenant->address);

        tenancy()->end();
    }

    public function test_tenant_contact_information_falls_back_to_admin_credentials(): void
    {
        $id = 'testschoolfallback' . uniqid();
        $domain = $id . '.localhost';

        $tenant = Tenant::create([
            'id' => $id,
            'name' => 'Beacon Academy',
            'status' => 'active',
            'admin_phone' => '+92 321 5556677',
            'admin_email' => 'admin@beacon.test',
        ]);

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        tenancy()->initialize($tenant);

        $response = $this->get("http://{$domain}/");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Tenant/Landing')
                ->where('school.phone', '+92 321 5556677')
                ->where('school.email', 'admin@beacon.test')
                ->where('tenant.phone', '+92 321 5556677')
                ->where('tenant.email', 'admin@beacon.test')
        );

        tenancy()->end();
    }
}
