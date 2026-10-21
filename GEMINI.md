<!-- GSD:project-start source:PROJECT.md -->

## Project

**LMS Multi-Tenant SaaS Platform**

A multi-tenant School Management System (SMS) and Learning Management System (LMS) SaaS platform built with Laravel 12, Stancl Tenancy, Inertia.js, and Vue 3. It provides central SaaS subscription management, isolated school tenant portals (admin, teacher, student, parent), fee collection (challans, JazzCash, Stripe), and comprehensive ERP modules (HR, payroll, inventory, hostel, library, transport).

**Core Value:** Strict multi-tenant data isolation and production-grade security across school management workflows, ensuring no data leaks across tenants or unauthorized roles while maintaining high-performance background asynchronous operations.

### Constraints

- **Compatibility:** Must maintain backwards compatibility with existing school tenants and SQLite local development workflows while supporting MySQL/PostgreSQL in production.
- **Tenancy Guardrails:** Tenant-specific database operations must never execute against the central database and vice-versa.
- **Performance:** Bulk operations must not block HTTP request cycles; queue worker failures must be retryable.

<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->

## Technology Stack

## Languages

- PHP 8.2+ (`^8.2` defined in `composer.json`) - Core backend application, tenancy middleware, REST/Inertia controllers, services, database models, migrations.
- JavaScript / ECMAScript (ES2022+ / Vue 3 Composition API) - Client-side single-page UI built via Inertia.js Vue 3.
- SQL (SQLite for local dev, MySQL / PostgreSQL schema support via Stancl Tenancy managers).
- CSS / Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`, `tailwindcss: ^4.0.0`) for utility-first styling.

## Runtime

- PHP CLI & Web SAPI 8.2+
- Node.js 20.x+ (used for Vite bundle compilation and frontend tooling)
- Composer (`composer.json`, lockfile `composer.lock` present)
- npm (`package.json`, lockfile `package-lock.json` present)

## Frameworks

- Laravel Framework 12.0 (`laravel/framework: ^12.0`) - Full-stack PHP application framework
- Inertia.js Laravel adapter (`inertiajs/inertia-laravel: ^3.1`) & `@inertiajs/vue3: ^3.6.1` - Monolithic single-page application bridge
- Vue 3 (`vue: ^3.5.39`) - Reactive frontend component layer
- Stancl Tenancy v3 (`stancl/tenancy: ^3.10`) - Multi-database and single-database multi-tenancy engine with subdomain/domain resolution and tenant lifecycle hooks
- Spatie Laravel Permission (`spatie/laravel-permission: ^6.25`) - Role-based access control (RBAC) package
- PHPUnit 11.5 (`phpunit/phpunit: ^11.5.50`) - Automated testing suite
- Mockery 1.6 (`mockery/mockery: ^1.6`) - Mocking library for unit/feature tests
- Vite 7.0 (`vite: ^7.0.7`) - Next-generation frontend build tool and hot module replacement dev server
- `@vitejs/plugin-vue: ^6.0.7` - Vue Single File Component support
- `laravel-vite-plugin: ^2.0.0` - Integration between Laravel and Vite asset bundling
- Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`)
- Lucide Vue Next (`lucide-vue-next: ^1.0.0`) - Icon system
- Concurrently (`concurrently: ^9.0.1`) - Parallel execution of artisan serve, queue listener, log tailing, and Vite dev server

## Key Dependencies

- `stancl/tenancy` (v3.10) - Isolates database connections, cache tags, filesystem disks, and queues per school tenant (`app/Models/Tenant.php`, `config/tenancy.php`).
- `inertiajs/inertia-laravel` (v3.1) - Renders Vue SPA pages directly from Laravel controllers without boilerplate REST serializing (`app/Http/Middleware/HandleInertiaRequests.php`).
- `spatie/laravel-permission` (v6.25) - Manages roles and permissions for school staff, teachers, students, and parents.
- `stripe/stripe-php` (v21.0) - SaaS subscription checkout, payment processing, and webhooks (`app/Http/Controllers/Central/SaaSCheckoutController.php`).
- `tightenco/ziggy` (v2.6) - Exposes named Laravel routes to JavaScript/Vue client components.
- `laravel/pail` (v1.2.2) - Real-time terminal log streaming for local debugging.
- `laravel/sail` (v1.41) - Docker environment integration for Laravel development.
- `laravel/pint` (v1.24) - Opinionated PHP code style fixer.

## Configuration

- Configured via `.env` files.
- Single `.env` file currently exists with hardcoded development values (`APP_ENV=local`, `APP_DEBUG=true`, SQLite connection, database queue and session).
- Environment separation (local, staging, production) is currently missing and required.
- `vite.config.js` - Configures Vite plugins (`laravel-vite-plugin`, `@vitejs/plugin-vue`, `@tailwindcss/vite`).
- `phpunit.xml` - PHPUnit environment test runner configuration.
- `composer.json` - Autoloading, scripts (`dev`, `test`, `setup`), minimum stability.

## Platform Requirements

- PHP 8.2+ with extensions: `pdo_sqlite`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`.
- Node.js 18+ and npm.
- Local SQLite database engine or MySQL 8.0+.
- Production Web Server (Nginx / Caddy / Apache) with PHP 8.2+ FPM.
- Production Database: MySQL 8.0+ or PostgreSQL 15+ (requires `CREATE DATABASE` privilege for tenant provisioning in multi-database mode).
- In-memory store: Redis 7.0+ for production cache, session, and queues.
- S3-compatible object storage (AWS S3, Cloudflare R2, or MinIO) for tenant isolated file storage.

<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->

## Conventions

## Code Style & Formatting

- **Standards:** Compliant with PSR-12 and standard Laravel formatting rules.
- **Linter & Code Fixer:** Laravel Pint (`vendor/bin/pint`) configured in `composer.json`.
- **Type Safety:** Strict typing (`declare(strict_types=1);`) used in routes and newer tenant controllers. Method arguments and return types are strongly encouraged for all service and controller methods.

## Architecture & Tenancy Conventions

### Multi-Tenancy Data Scoping

### Authorization & RBAC

- **Roles:** Configured via `spatie/laravel-permission` with guard `'web'`.
- **Expected Roles:** `Super Admin` (Central), `Principal` / `Admin`, `Teacher`, `Student`, `Parent` / `Guardian`, `Accountant`, `Librarian` (Tenant).
- **Enforcement Pattern:**

### Service Layer Conventions

- Controllers should delegate business workflows (provisioning, fee processing, admissions, notifications, payment gateway interactions) to dedicated classes in `app/Services/`.
- Services must return structured results or typed models rather than directly rendering HTTP responses.

### Background Queue Conventions

- Time-consuming, bulk, or network-bound tasks must implement `Illuminate\Contracts\Queue\ShouldQueue` and be dispatched to the queue:
- Queued jobs running on behalf of a tenant must ensure tenant context is bootstrapped via `Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper` or implement `Stancl\Tenancy\Contracts\TenantAwareJob`.

### File Storage Conventions

- Uploaded files must use Laravel's `Storage` facade with tenant-aware disks (`Storage::disk('public')->putFile(...)` or `Storage::disk('local')->putFile(...)`).
- Direct file movement into `public/uploads/` via `$file->move(...)` violates multi-tenant isolation and security requirements.
- Private student documents (e.g. B-forms, CNICs, academic records) must be stored on non-public disks and served via authorization-protected download controllers.

### Logging Conventions

- Avoid logging raw session IDs, auth tokens, passwords, credit card details, or sensitive PII (student phone numbers, parent CNICs).
- Sanitize or mask message bodies in notification logs.

<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->

## Architecture

## Pattern Overview

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
- **Tenant Controllers (`app/Http/Controllers/Tenant/`):**

### 4. Service Layer

- Encapsulates domain logic and external integrations:

### 5. Data & Persistence Layer

- **Central Models:** Models operating strictly on the central database connection:
- **Tenant Models:** Models operating on dynamically switched tenant database:

## Data Flow

### Tenant HTTP Request Lifecycle:

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

<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->

## Project Skills

No project skills found. Add skills to any of: `.agents/skills/`, `.agents/skills/`, `.cursor/skills/`, `.github/skills/`, or `.codex/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->

## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-fast` for a trivial task inline, with no subagents and no PLAN.md
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->

## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
