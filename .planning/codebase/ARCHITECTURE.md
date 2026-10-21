---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# Architecture

**Analysis Date:** 2026-10-22

## Pattern Overview

**Overall:** Multi-Tenant SaaS Platform with Monolithic Laravel Backend and Inertia.js / Vue 3 Frontend.

**Key Characteristics:**
- **Domain-Driven Multi-Tenancy:** Multi-database tenancy powered by `stancl/tenancy`. Central domains host the SaaS marketing site, registration/checkout, and Super Admin dashboard. Subdomains host isolated school management portals.
- **Database-Per-Tenant Isolation:** Central database stores system-wide models (tenants, domains, SaaS plans, global transactions). Each tenant has an isolated database schema containing users, students, academic records, challans, and ERP data.
- **Full-Stack Monolith with SPA Experience:** Inertia.js bridges Laravel controllers with Vue 3 client-side components without traditional client-server REST API duplication.

## Layers

### 1. Routing & Tenancy Identification Layer

- **Central Routes (`routes/web.php`):** Bound to central domains (`localhost`, IP, or configured production apex domain). Manages landing pages, checkout (`SaaSCheckoutController`), Stripe webhooks, and Super Admin portal (`SuperAdminController`).
- **Tenant Routes (`routes/tenant.php`):** Guarded by `InitializeTenancyByDomain` and `PreventAccessFromCentralDomains`. Resolves tenant from request hostname, switches database connection, activates cache tags, and isolates filesystem roots.

### 2. Middleware Layer

- **`CheckMaintenanceMode` (`app/Http/Middleware/CheckMaintenanceMode.php`):** Intercepts requests to check global platform maintenance status from central database (`SaaSSetting`). Bypasses for super-admin paths.
- **`CheckTenantStatus` (`app/Http/Middleware/CheckTenantStatus.php`):** Verifies that resolved tenant status is `'active'`. Redirects to suspension/overdue notices if tenant is expired or disabled.
- **`EnforceTenantQuotas` (`app/Http/Middleware/EnforceTenantQuotas.php`):** Checks resource constraints (e.g., student enrollment limits) based on subscribed plan features.
- **`HandleInertiaRequests` (`app/Http/Middleware/HandleInertiaRequests.php`):** Shares global props (authenticated user, tenant info, flash messages, navigation state) with Vue frontend.
- **`LogTenantRequests` (`app/Http/Middleware/LogTenantRequests.php`):** Audit logger for incoming HTTP tenant requests.

### 3. Controller Layer

- **Central Controllers (`app/Http/Controllers/Central/`):**
  - `CentralAuthController`: Central administrative login.
  - `SaaSCheckoutController`: Tenant onboarding, plan checkout, Stripe payment handling.
  - `SuperAdminController`: Global tenant overview, impersonation token generation, subscription management.
- **Tenant Controllers (`app/Http/Controllers/Tenant/`):**
  - `TenantAuthController`: School portal login and session termination.
  - `AdminPortalController`: Principal dashboard, classes, sections, notices, fee defaulters.
  - `TeacherPortalController`: Teacher assignment management, attendance marking, grading.
  - `StudentPortalController`: Student portal, assignment submissions, report card download.
  - `ParentPortalController`: Parent portal, child fee challan overview, mobile wallet payments.
  - `StaffController`, `StudentController`, `TimetableController`, `FeeStructureController`.
  - `ERP/*`: HR & payroll, inventory, hostel management, library, transport, certificate generation.

### 4. Service Layer

- Encapsulates domain logic and external integrations:
  - `TenantProvisioningService` (`app/Services/TenantProvisioningService.php`): Executes tenant database creation, migration (`tenants:migrate`), seeding, and default school setup.
  - `FeeService` (`app/Services/FeeService.php`): Fee calculation, challan generation, manual and gateway payment recording.
  - `AdmissionService` (`app/Services/AdmissionService.php`): Student enrollment, merit evaluation, and admission workflows.
  - `JazzCashService` (`app/Services/JazzCashService.php`): Merchant form payload preparation and HMAC-SHA256 signature verification.
  - `NotificationService` (`app/Services/NotificationService.php`): SMS and WhatsApp dispatch logic.
  - `AiVisionService` (`app/Services/AiVisionService.php`): Face recognition and assignment AI evaluation.

### 5. Data & Persistence Layer

- **Central Models:** Models operating strictly on the central database connection:
  - `Tenant` (`app/Models/Tenant.php`), `Domain` (Stancl default), `Referral` (`app/Models/Referral.php`), `SaaSTransaction` (`app/Models/SaaSTransaction.php`).
  - *Identified Gap:* `SubscriptionPlan`, `Coupon`, `SaaSSetting`, and `SaaSActivityLog` lack explicit `CentralConnection` trait, leading to errors or requiring manual `tenancy()->central(...)` closures.
- **Tenant Models:** Models operating on dynamically switched tenant database:
  - `User` (`app/Models/User.php`), `Student` (`app/Models/Student.php`), `SchoolClass`, `Section`, `Attendance`, `FeeChallan`, `FeeStructure`, `Leave`, `Exam`, `Notice`, ERP models.

## Data Flow

### Tenant HTTP Request Lifecycle:

1. Browser requests `https://school1.example.com/admin/dashboard`.
2. `public/index.php` initializes Laravel application container via `bootstrap/app.php`.
3. Route matched in `routes/tenant.php`.
4. Middleware pipeline executes:
   - `InitializeTenancyByDomain` extracts host (`school1.example.com`), locates tenant ID in central DB, and triggers `DatabaseTenancyBootstrapper`.
   - Default database connection dynamically repointed to tenant DB (`tenant{id}`).
   - `CheckTenantStatus` verifies tenant account is active.
   - `HandleInertiaRequests` resolves authenticated tenant user and shared props.
5. Controller action invoked (`AdminPortalController::index`).
6. Eloquent queries execute against tenant's database connection.
7. Controller returns `Inertia::render('Tenant/Admin/Dashboard', [...])`.
8. Inertia serializes props into HTML page payload (or JSON response for subsequent SPA visits).

## Key Abstractions

- **`Tenant` (`App\Models\Tenant`):** Subclasses Stancl `BaseTenant` implementing `TenantWithDatabase`. Holds custom columns (`name`, `status`), domain relationships, and subscription metadata.
- **`CentralConnection` (`Stancl\Tenancy\Database\Concerns\CentralConnection`):** Trait applied to Eloquent models that must always target central database regardless of whether tenancy is initialized.
- **`FeeChallan` (`App\Models\FeeChallan`):** Central financial unit for school operations, tracking line items, late fees, payment status (`unpaid`, `paid`, `overdue`), and audit trails.

## Entry Points

- **HTTP Web Requests:** `public/index.php` bootstraps application and routes through `bootstrap/app.php`.
- **CLI Commands:** `artisan` handles command execution (e.g. `php artisan tenants:migrate`, `php artisan queue:listen`).
- **Health Check Route:** Built-in `health: '/up'` registered in `bootstrap/app.php` responding at `/up`.

## Error Handling

- Centralized in `bootstrap/app.php` using Laravel 12 `$exceptions->render(...)` closures.
- Specially catches `TenantCouldNotBeIdentifiedException` and renders custom Inertia view `Errors/TenantUnavailable` with 404 status.
- Catches `NotFoundHttpException` on tenant subdomains and displays school-branded 404 error page.

## Cross-Cutting Concerns

- **Multi-Tenancy Isolation:** Maintained via connection switching. Models inside tenant context should never read central tenant tables without explicit scoping.
- **Authorization:** Managed via `spatie/laravel-permission` traits on `User`. *Critical Gap:* Policies and route authorization middleware are currently missing across the majority of tenant controllers.
- **File Storage Isolation:** Storage disks are defined in `config/filesystems.php` and configured for tenancy in `config/tenancy.php`. *Critical Gap:* Several controllers currently move files directly to `public/uploads/` instead of using the tenant-scoped storage disk.

---

*Architecture analysis: 2026-10-22*
*Update when major patterns change*
