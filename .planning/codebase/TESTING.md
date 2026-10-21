---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# Testing Practices

**Analysis Date:** 2026-10-22

## Framework & Environment

- **Test Framework:** PHPUnit 11.5 (`phpunit/phpunit: ^11.5.50`).
- **Configuration:** `phpunit.xml`.
  - Runs in-memory SQLite database (`:memory:`) or dedicated test database.
  - Disables cache and resets config during test runs (`@php artisan config:clear --ansi` before `php artisan test`).
- **Execution Command:**
  ```bash
  composer test
  # or
  php artisan test
  ```

## Test Directory Structure

```
tests/
├── TestCase.php                 # Base test case inheriting Illuminate\Foundation\Testing\TestCase
├── Feature/                     # High-level HTTP and domain integration tests
│   ├── AdmissionsTest.php       # Inquiry, merit list, student enrollment workflows
│   ├── ERPModulesTest.php       # ERP features (HR, inventory, library, hostel, transport)
│   ├── FeeGenerationTest.php    # Fee challan generation, discounts, and overrides
│   ├── PaymentsWebhookTest.php  # Payment gateway IPN and webhook verification
│   ├── SaaSGapsTest.php         # Central onboarding and billing edge cases
│   └── TenantProvisioningTest.php # School tenant database creation & seeding
└── Unit/
    └── ExampleTest.php          # Basic unit sanity test
```

## Tenancy Testing Pattern

Because the application uses Stancl Tenancy with separate database connections, feature tests follow a specific lifecycle:

```php
use Tests\TestCase;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeatureTestExample extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Provision isolated test tenant
        $id = 'testschool' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test School Campus',
            'admin_email' => 'admin@school.test',
            'admin_password' => 'Secret@123',
        ]);

        // 2. Initialize tenancy to switch default connection to tenant DB
        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        // 3. Reset tenancy connection cleanly
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }
}
```

## Testing Gaps & Areas for Improvement

1. **Authorization & RBAC Tests:**
   - There are currently no tests asserting that an authenticated `Student` or `Parent` is forbidden (403 Forbidden) from accessing `AdminPortalController` routes (e.g. `/admin/dashboard`, `/users`, `/admissions`).
2. **IDOR & Scoping Tests:**
   - No tests verifying that Parent A cannot print or pay Fee Challan belonging to Parent B's student.
3. **Queue & Job Tests:**
   - Background jobs (`SendAbsentSmsJob`, fee generation, imports) lack automated queue assertion tests (`Queue::fake()`, `Queue::assertPushed(...)`).
4. **Environment & Security Header Tests:**
   - No automated tests verifying that `APP_DEBUG` is false in production and session cookies have `Secure`, `HttpOnly`, and appropriate `SameSite` flags.
5. **Health-Check Tests:**
   - Need test coverage for health endpoints ensuring 200 OK responses without leaking database credentials or hostnames.

---

*Testing analysis: 2026-10-22*
*Update after test additions*
