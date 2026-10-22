<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SaaSSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SuperAdminProfileAndUsersTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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
            'name' => 'Ibad Shalmany',
            'email' => 'ibadshalmany@gmail.com',
            'password' => Hash::make('oldpassword123'),
        ]);
    }

    public function test_super_admin_can_update_profile_name_and_email(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/super-admin/profile', [
            'name' => 'Ibad Shalmany (Updated)',
            'email' => 'ibad.updated@gmail.com',
        ]);

        $response->assertSessionHas('success');
        $this->adminUser->refresh();
        $this->assertEquals('Ibad Shalmany (Updated)', $this->adminUser->name);
        $this->assertEquals('ibad.updated@gmail.com', $this->adminUser->email);
    }

    public function test_super_admin_can_update_password_with_current_password_check(): void
    {
        // 1. Wrong current password fails
        $failResponse = $this->actingAs($this->adminUser)->post('/super-admin/profile', [
            'name' => $this->adminUser->name,
            'email' => $this->adminUser->email,
            'current_password' => 'wrongpass',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $failResponse->assertSessionHasErrors(['current_password']);

        // 2. Correct current password succeeds
        $successResponse = $this->actingAs($this->adminUser)->post('/super-admin/profile', [
            'name' => $this->adminUser->name,
            'email' => $this->adminUser->email,
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $successResponse->assertSessionHas('success');
        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->adminUser->password));
    }

    public function test_super_admin_can_add_new_central_administrator(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/super-admin/users', [
            'name' => 'Second Admin',
            'email' => 'secondadmin@raibix.com',
            'password' => 'strongsecret123',
            'password_confirmation' => 'strongsecret123',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'secondadmin@raibix.com',
            'name' => 'Second Admin',
        ]);
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)->delete("/super-admin/users/{$this->adminUser->id}");
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_super_admin_can_delete_other_administrator(): void
    {
        $otherAdmin = User::create([
            'name' => 'Temporary Staff',
            'email' => 'tempstaff@raibix.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/super-admin/users/{$otherAdmin->id}");
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $otherAdmin->id]);
    }
}
