<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class HealthCheckSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testhealth' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Health Check Campus',
            'admin_email' => 'admin@health.test',
            'admin_password' => 'Secret@123',
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
     * Test central health check endpoint returns 200 with sanitized json payload.
     */
    public function test_central_health_check_returns_ok_and_sanitized_payload(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'services' => [
                'database' => 'up',
                'cache' => 'up',
            ],
        ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('root', strtolower($content));
        $this->assertStringNotContainsString('sqlite', strtolower($content));
    }

    /**
     * Test central health alias (/health) functions identically.
     */
    public function test_central_health_alias_returns_ok(): void
    {
        $response = $this->get('/health');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    /**
     * Test tenant-scoped health check returns 200 with isolated tenant services verified.
     */
    public function test_tenant_health_check_returns_ok_and_sanitized_payload(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/api/tenant-health");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'tenant' => 'active',
            'services' => [
                'database' => 'up',
                'cache' => 'up',
                'storage' => 'up',
            ],
        ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('trace', strtolower($content));
    }

    /**
     * Test tenant health check alias (/api/tenant/health).
     */
    public function test_tenant_health_check_alias_returns_ok(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/api/tenant/health");
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok', 'tenant' => 'active']);
    }
}
