# Roadmap: LMS Multi-Tenant SaaS Platform

## Overview

This roadmap defines the audit, hardening, multi-tenancy verification, authorization enforcement, asynchronous queueing refactor, and security remediation for the Laravel 12 multi-tenant LMS SaaS application.

## Phases

- [x] **Phase 1: Tenancy & Migration Architecture Audit** - Audit central vs tenant migrations, configure explicit database connections, and add CentralConnection traits.
- [x] **Phase 2: Environment Configuration & Production Hardening** - Separate local/staging/production environment configurations and enforce secure cookies, session encryption, and Redis stores.
- [ ] **Phase 3: Authentication, Authorization & RBAC Hardening** - Implement Spatie role/permission middleware across tenant routes and introduce Laravel authorization policies.
- [ ] **Phase 4: Identifier Scoping & IDOR Remediation** - Eliminate unscoped global model lookups (`findOrFail($id)`) and enforce tenant and user ownership checks.
- [ ] **Phase 5: Asynchronous Queueing & Job Refactoring** - Convert heavy synchronous operations (fee generation, CSV imports, external SMS/WhatsApp calls) into tenant-aware queued jobs.
- [ ] **Phase 6: Safe Health-Check Endpoints** - Implement central and tenant health check endpoints without exposing credentials or sensitive system information.
- [ ] **Phase 7: Log Sanitization & Secure Storage Isolation** - Sanitize session IDs and PII from application logs and migrate insecure public uploads to protected tenant-scoped storage.

## Phase Details

### Phase 1: Tenancy & Migration Architecture Audit
**Goal**: Verify multi-tenant database configuration, validate migration separation between central and tenant schemas, and ensure central models consistently target the central database.
**Depends on**: Nothing
**Requirements**: TEN-01, TEN-02, TEN-03, TEN-04
**Success Criteria**:
  1. Central models (`SubscriptionPlan`, `Coupon`, `SaaSSetting`, `SaaSActivityLog`, `TenantSubscription`) use `CentralConnection` trait and query central DB without manual `tenancy()->central()` wrappers.
  2. `config/database.php` has explicit `'central'` and tenant template connections configured and wired into `config/tenancy.php`.
  3. Running `php artisan migrate` and `php artisan tenants:migrate` executes without schema conflicts or missing table errors.
**Plans**: 2 plans

Plans:
- [x] 01-01: Audit migration separation and add CentralConnection traits to central models.
- [x] 01-02: Configure explicit central and tenant template connections in database and tenancy configs.

### Phase 2: Environment Configuration & Production Hardening
**Goal**: Establish environment presets and harden production runtime settings for cookies, sessions, cache, and debugging.
**Depends on**: Phase 1
**Requirements**: ENV-01, ENV-02, ENV-03, ENV-04
**Success Criteria**:
  1. `.env.example`, `.env.staging.example`, and `.env.production.example` exist with all required production keys.
  2. Production configuration enforces `APP_DEBUG=false`, preventing stack trace or credential leaks on errors.
  3. Session cookies in production enforce `Secure`, `HttpOnly`, `SameSite=lax`, and `SESSION_ENCRYPT=true` with subdomain-safe scoping.
  4. Redis drivers configured for production cache, session, and queues with tenant prefixing.
**Plans**: 2 plans

Plans:
- [x] 02-01: Create environment presets (.env.example, .env.staging.example, .env.production.example).
- [x] 02-02: Harden session, cookie, cache, and error configuration in config files.

### Phase 3: Authentication, Authorization & RBAC Hardening
**Goal**: Close authorization loopholes in tenant portals so users cannot access endpoints outside their assigned role.
**Depends on**: Phase 2
**Requirements**: AUTH-01, AUTH-02, AUTH-03
**Success Criteria**:
  1. Tenant route groups in `routes/tenant.php` enforce Spatie role middleware (`role:Principal|Admin`, `role:Teacher`, `role:Student`, `role:Parent`).
  2. Students or parents attempting to access `/admin/*` or `/teacher/*` routes receive HTTP 403 Forbidden.
  3. Resource policies (`FeeChallanPolicy`, `StudentPolicy`, `StaffPolicy`, `AssignmentPolicy`) enforce object-level ownership checks.
**Plans**: 2 plans

Plans:
- [x] 03-01: Apply role and permission middleware groups across `routes/tenant.php`.
- [x] 03-02: Generate and register Laravel authorization policies for core tenant models.

### Phase 4: Identifier Scoping & IDOR Remediation
**Goal**: Ensure all tenant data access is strictly scoped to authenticated user ownership, eliminating Insecure Direct Object References.
**Depends on**: Phase 3
**Requirements**: SCOPE-01, SCOPE-02, SCOPE-03, SCOPE-04
**Success Criteria**:
  1. `FeeStructureController::printChallan` and `ParentPortalController::payChallan` only allow parents/students to view or pay their own challans.
  2. Student records, report cards, exam marks, and observations cannot be viewed across students by guessing route IDs.
  3. Automated feature tests verify that manipulating ID parameters returns HTTP 403 or 404 for unauthorized users.
**Plans**: 2 plans

Plans:
- [x] 04-01: Audit and scope fee challan and payment endpoints in FeeStructureController, ParentPortalController, and PaymentController.
- [x] 04-02: Audit and scope student records, report cards, submissions, and ERP resource endpoints.

### Phase 5: Asynchronous Queueing & Job Refactoring
**Goal**: Offload heavy database queries, bulk loops, and third-party network requests into tenant-aware background jobs.
**Depends on**: Phase 4
**Requirements**: QUEUE-01, QUEUE-02, QUEUE-03, QUEUE-04
**Success Criteria**:
  1. `generateMonthlyChallans` dispatches `GenerateMonthlyFeeChallansJob` without blocking the HTTP request thread.
  2. CSV student and staff imports run asynchronously via `ImportStudentsJob` with progress tracking.
  3. SMS and WhatsApp notifications in `NotificationService` dispatch asynchronously via queued jobs.
  4. All queued jobs execute with tenant database context properly restored by the queue worker.
**Plans**: 2 plans

Plans:
- [x] 05-01: Refactor bulk fee generation and CSV imports into queued background jobs.
- [x] 05-02: Refactor notification dispatch and external API calls into tenant-aware queued jobs.

### Phase 6: Safe Health-Check Endpoints
**Goal**: Provide secure liveness and readiness monitoring endpoints that report application status without leaking sensitive topology.
**Depends on**: Phase 2
**Requirements**: HEALTH-01, HEALTH-02, HEALTH-03
**Success Criteria**:
  1. `/api/health` responds with HTTP 200 and `{"status": "ok"}` when central database and cache are operational.
  2. Tenant-scoped health check verifies tenant database connectivity and storage writeability.
  3. Service outages return HTTP 503 without revealing database hostnames, usernames, passwords, or stack traces.
**Plans**: 1 plan

Plans:
- [x] 06-01: Implement sanitized central and tenant health check endpoints with automated tests.

### Phase 7: Log Sanitization & Secure Storage Isolation
**Goal**: Strip sensitive tokens and PII from logs and secure uploaded student and financial documents.
**Depends on**: Phase 4
**Requirements**: LOG-01, LOG-02, LOG-03, LOG-04
**Success Criteria**:
  1. `LogTenantRequests` logs request telemetry without recording active session IDs.
  2. `NotificationService` masks phone numbers, OTPs, student names, and payment details in log outputs.
  3. Student documents (B-forms, CNICs) and payment receipts are moved to private storage disks instead of `public/uploads/`.
  4. Private documents are accessible only via authorization-guarded download controllers.
**Plans**: 2 plans

Plans:
- [x] 07-01: Sanitize logging in `LogTenantRequests` and `NotificationService`.
- [x] 07-02: Refactor file uploads from `public/uploads/` to protected storage disks with authorized streaming controllers.

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Tenancy & Migration Architecture Audit | 2/2 | Complete | 2026-10-22 |
| 2. Environment Configuration & Production Hardening | 2/2 | Complete | 2026-10-22 |
| 3. Authentication, Authorization & RBAC Hardening | 2/2 | Complete | 2026-10-22 |
| 4. Identifier Scoping & IDOR Remediation | 2/2 | Complete | 2026-10-22 |
| 5. Asynchronous Queueing & Job Refactoring | 2/2 | Complete | 2026-10-22 |
| 6. Safe Health-Check Endpoints | 1/1 | Complete | 2026-10-22 |
| 7. Log Sanitization & Secure Storage Isolation | 2/2 | Complete | 2026-10-22 |
