# LMS Multi-Tenant SaaS Platform

## What This Is

A multi-tenant School Management System (SMS) and Learning Management System (LMS) SaaS platform built with Laravel 12, Stancl Tenancy, Inertia.js, and Vue 3. It provides central SaaS subscription management, isolated school tenant portals (admin, teacher, student, parent), fee collection (challans, JazzCash, Stripe), and comprehensive ERP modules (HR, payroll, inventory, hostel, library, transport).

## Core Value

Strict multi-tenant data isolation and production-grade security across school management workflows, ensuring no data leaks across tenants or unauthorized roles while maintaining high-performance background asynchronous operations.

## Business Context

- **Customer:** Private and public K-12 schools, academies, and educational institutions paying subscription fees for institutional management.
- **Revenue model:** Tiered SaaS recurring subscriptions and transaction fees on digital fee payments.
- **Success metric:** Zero cross-tenant data leaks, sub-second API/Inertia response times, 100% reliable fee processing, and production readiness.

## Requirements

### Validated

- [x] Multi-database tenant architecture with domain/subdomain routing (`stancl/tenancy`).
- [x] Single-page application UI with Inertia.js Vue 3 and Tailwind CSS.
- [x] Role-based models and migrations for students, teachers, parents, and administrative staff.
- [x] Core ERP modules: HR, payroll, library, inventory, hostel, transport, certificate issuance.
- [x] Stripe billing for central subscriptions and JazzCash payment integration for school fee challans.

### Active

- [ ] **REQ-AUDIT-1 (Audit):** Audit current Laravel application, tenancy configuration, migrations, authentication, authorization, queues, and storage usage.
- [ ] **REQ-ENV-2 (Environments):** Separate local, staging, and production environment configuration (`.env.example`, `.env.staging.example`, `.env.production.example`).
- [ ] **REQ-SEC-3 (Production Hardening):** Ensure production configuration has `APP_DEBUG` disabled and production-safe session, cookie, and cache settings (`SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT`, `SESSION_DOMAIN`, Redis drivers).
- [ ] **REQ-TEN-4 (Tenant Review & RBAC):** Review all tenant-aware models, middleware, jobs, policies, and API endpoints; add `CentralConnection` trait to central models and implement role/permission middleware and policies across tenant routes.
- [ ] **REQ-IDOR-5 (Identifier Scoping):** Verify that no tenant data is accessed using unscoped global identifiers; enforce ownership validation on challans, student records, submissions, and payment routes.
- [ ] **REQ-QUEUE-6 (Queued Jobs):** Identify and refactor synchronous operations into queued jobs (bulk fee challan generation, CSV student/staff imports, SMS/WhatsApp external notifications, AI face/assignment evaluation).
- [ ] **REQ-HEALTH-7 (Health Checks):** Add health-check endpoints that verify database, cache, queue, and storage health without exposing sensitive infrastructure or credentials.
- [ ] **REQ-LOG-8 (Log Sanitization & Storage Security):** Ensure logs do not contain session IDs, passwords, tokens, or PII; refactor raw `public_path('uploads/...')` storage to tenant-isolated storage disks with authorization protection for private documents.

### Out of Scope

- Complete UI redesign or rewrite of Vue 3 components (focus is on security, tenancy, performance, and infrastructure).
- Switching from multi-database tenancy to single-database row-level tenancy.
- Replacing Stancl Tenancy with another tenancy package.
- Altering fundamental academic calculation rules or core school business logic.

## Context

- **Multi-Tenancy Engine:** Stancl Tenancy v3 with dynamic database connection switching (`DatabaseTenancyBootstrapper`), cache tagging, and queue context passing.
- **Frontend/Backend Architecture:** Monolithic Laravel 12 application with Inertia.js Vue 3 SPA frontend.
- **Identified Critical Gaps:**
  - Missing `CentralConnection` trait on `SubscriptionPlan`, `Coupon`, `SaaSSetting`, `SaaSActivityLog`.
  - Missing authorization middleware and policies on `routes/tenant.php` (all endpoints currently accessible by any authenticated user).
  - Unscoped model lookups via route parameters (`findOrFail($id)` without tenant/parent scoping).
  - Heavy synchronous loops (bulk fee generation, imports, notification API calls) executing inside HTTP requests.
  - Sensitive session IDs and PII logged in `LogTenantRequests` and `NotificationService`.
  - Sensitive files (fee receipts, student birth certificates, CNICs) uploaded directly to `public/uploads/`.

## Constraints

- **Compatibility:** Must maintain backwards compatibility with existing school tenants and SQLite local development workflows while supporting MySQL/PostgreSQL in production.
- **Tenancy Guardrails:** Tenant-specific database operations must never execute against the central database and vice-versa.
- **Performance:** Bulk operations must not block HTTP request cycles; queue worker failures must be retryable.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Multi-Database Tenancy | Provides strict physical isolation of school database records | ✓ Good |
| CentralConnection Trait | Eliminates brittle `tenancy()->central(...)` query wrappers | Pending |
| Spatie RBAC & Policies | Enforces explicit role and object-level permissions on tenant routes | Pending |
| Queue Heavy Workflows | Prevents 504 Gateway Timeouts during bulk challan and import tasks | Pending |
| Isolated Storage Disks | Prevents unauthenticated access to sensitive student PII and payment slips | Pending |

---
*Last updated: 2026-10-22 after initial codebase audit*
