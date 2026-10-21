---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# Codebase Structure

**Analysis Date:** 2026-10-22

## Directory Layout

```
LMS/
├── app/
│   ├── Console/Commands/        # Custom Artisan CLI commands
│   ├── Events/                  # Domain event classes
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Central/         # Central SaaS controllers (onboarding, checkout, super-admin)
│   │   │   └── Tenant/          # School tenant controllers (admin, teachers, students, parents)
│   │   │       └── ERP/         # School ERP modules (HR, finance, hostel, library, transport, etc.)
│   │   └── Middleware/          # Tenancy, inertia, quota, maintenance, and audit middleware
│   ├── Jobs/                    # Queued background jobs (OffboardTenantJob, SendAbsentSmsJob)
│   ├── Mail/                    # Mailable notification classes
│   ├── Models/                  # Eloquent models (central & tenant-specific)
│   ├── Providers/               # Service providers (AppServiceProvider)
│   └── Services/                # Business logic services (Fee, Admission, Provisioning, JazzCash)
├── bootstrap/
│   └── app.php                  # Application bootstrap, routing, middleware & exception handlers
├── config/                      # Application configuration files (app, tenancy, database, session, etc.)
├── database/
│   ├── factories/               # Model factories for testing and seeding
│   ├── migrations/              # Central migrations (tenants, domains, SaaS plans, global users)
│   │   └── tenant/              # Tenant-specific migrations (school users, academic, fee, ERP tables)
│   └── seeders/                 # Database seeders (DatabaseSeeder, TenantSeeder)
├── public/                      # Web server docroot (index.php, static assets, public uploads)
│   └── uploads/                 # Direct uploaded files (logos, student docs, fee receipts)
├── resources/
│   ├── css/                     # Tailwind CSS style definitions
│   ├── js/                      # Inertia.js Vue 3 SPA frontend
│   │   ├── Components/          # Shared Vue UI widgets and dialogs
│   │   ├── Layouts/             # Admin, Teacher, Student, Parent, and Central layouts
│   │   └── Pages/               # Vue page components corresponding to Inertia routes
│   └── views/                   # Root Blade template (app.blade.php)
├── routes/
│   ├── console.php              # Scheduled and closure-based console commands
│   ├── tenant.php               # Tenant subdomain routes (isolated school portal)
│   └── web.php                  # Central apex domain routes (SaaS marketing, checkout, super-admin)
├── storage/                     # Framework logs, compiled templates, session/cache files
├── tests/
│   ├── Feature/                 # Integration tests (Admissions, Fee, Provisioning, Payments)
│   └── Unit/                    # Unit tests
└── vite.config.js               # Frontend bundle configuration
```

## Key Locations & Conventions

### Central vs Tenant Controllers

- **Central Controllers:** Located in `app/Http/Controllers/Central/`. Handles multi-tenant lifecycle and global billing.
  - Examples: `SuperAdminController.php`, `SaaSCheckoutController.php`, `CentralAuthController.php`.
- **Tenant Controllers:** Located in `app/Http/Controllers/Tenant/` and `app/Http/Controllers/Tenant/ERP/`. Operates strictly within active tenant context.
  - Examples: `AdminPortalController.php`, `FeeStructureController.php`, `TeacherPortalController.php`, `StudentPortalController.php`, `ParentPortalController.php`.

### Central vs Tenant Migrations

- **Central Database Migrations:** Located in `database/migrations/`. Executed via `php artisan migrate`. Manages `tenants`, `domains`, `subscription_plans`, `saas_settings`, `saas_transactions`, and central users.
- **Tenant Database Migrations:** Located in `database/migrations/tenant/`. Executed per tenant via `php artisan tenants:migrate`. Manages school-specific tables: `users`, `students`, `parents_profile`, `classes`, `sections`, `fee_challans`, `attendance`, and ERP tables.

### Models & Database Connections

- **Central Models:** Models mapped to central tables must use the `Stancl\Tenancy\Database\Concerns\CentralConnection` trait so queries target the central database even when called within tenant requests (`Referral.php`, `SaaSTransaction.php`).
- **Tenant Models:** Models without explicit connection defaults will automatically execute against the active tenant connection once tenancy is initialized.

### Naming Conventions

- **Controllers:** Resource or domain name followed by `Controller` in PascalCase (e.g. `FeeStructureController`, `AdminPortalController`).
- **Models:** Singular PascalCase corresponding to table name (e.g. `Student` -> `students`, `FeeChallan` -> `fee_challans`).
- **Routes:** Prefixed with route group naming:
  - Central routes: `central.*` (e.g. `central.dashboard`, `central.checkout`).
  - Tenant routes: `tenant.*` (e.g. `tenant.login`, `tenant.admin.dashboard`, `tenant.student.assignments`).

---

*Structure analysis: 2026-10-22*
*Update after directory reorganizations*
