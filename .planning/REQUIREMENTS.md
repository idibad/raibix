# Requirements: LMS Multi-Tenant SaaS Platform

**Defined:** 2026-10-22  
**Core Value:** Strict multi-tenant data isolation and production-grade security across school management workflows, ensuring no data leaks across tenants or unauthorized roles while maintaining high-performance background asynchronous operations.

## v1 Requirements

### Environment & Production Hardening (ENV)

- [x] **ENV-01**: Separate local, staging, and production environment configurations into dedicated sample files (`.env.example`, `.env.staging.example`, `.env.production.example`).
- [x] **ENV-02**: Enforce `APP_DEBUG=false` in production configuration with production-safe error page fallbacks.
- [x] **ENV-03**: Configure production-safe session, cookie, and cache settings (`SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, and subdomain-safe `SESSION_DOMAIN`).
- [x] **ENV-04**: Configure production Redis connections for cache, session, and queue drivers with tenancy prefixing.

### Tenancy Architecture & Database Integrity (TEN)

- [x] **TEN-01**: Complete systematic audit of central migrations (`database/migrations/`) and tenant migrations (`database/migrations/tenant/`) to verify clean separation.
- [x] **TEN-02**: Explicitly define `'central'` and tenant template connections in `config/database.php` and configure them in `config/tenancy.php`.
- [x] **TEN-03**: Add `Stancl\Tenancy\Database\Concerns\CentralConnection` trait to central models (`SubscriptionPlan`, `Coupon`, `SaaSSetting`, `SaaSActivityLog`, `TenantSubscription`).
- [x] **TEN-04**: Configure tenant database managers for production MySQL and PostgreSQL schemas with proper database creation grants.

### Authentication, Authorization & RBAC (AUTH)

- [ ] **AUTH-01**: Review authentication guards, redirect logic, and session lifecycles for both central and tenant portals.
- [ ] **AUTH-02**: Implement Spatie role and permission middleware groups in `routes/tenant.php` (restricting Admin, Teacher, Student, and Parent routes).
- [ ] **AUTH-03**: Create Laravel Policies (`FeeChallanPolicy`, `StudentPolicy`, `StaffPolicy`, `AssignmentPolicy`) to enforce object-level authorization checks.

### Tenant Scoping & Identifier Validation (SCOPE)

- [ ] **SCOPE-01**: Review all tenant-aware controllers and eliminate all unscoped global identifier lookups (`findOrFail($id)`).
- [ ] **SCOPE-02**: Scope `printChallan` and fee receipt endpoints so students and parents can only access their own records.
- [ ] **SCOPE-03**: Scope `payChallan` and payment callback handling to verify student/parent ownership before processing.
- [ ] **SCOPE-04**: Scope student records, gradebooks, report cards, and observation logs to the authorized user or parent relationship.

### Background Queueing & Asynchronous Workflows (QUEUE)

- [ ] **QUEUE-01**: Refactor bulk monthly fee challan generation (`FeeStructureController::generateMonthlyChallans`) into queued background job (`GenerateMonthlyFeeChallansJob`).
- [ ] **QUEUE-02**: Refactor CSV student and staff imports (`ImportController`) into queued background jobs with failure reporting.
- [ ] **QUEUE-03**: Refactor third-party SMS and WhatsApp notification calls in `NotificationService` to dispatch via background queues.
- [ ] **QUEUE-04**: Ensure all queued jobs dispatched within tenant context implement `TenantAwareJob` or boot tenancy dynamically.

### Safe Health Checks & Monitoring (HEALTH)

- [ ] **HEALTH-01**: Implement `/api/health` or `/health` endpoint verifying central database connectivity.
- [ ] **HEALTH-02**: Implement tenant-aware health check verifying tenant database connectivity, cache access, and storage writeability.
- [ ] **HEALTH-03**: Ensure all health-check responses return sanitized status payloads (`{"status":"ok"}`) and HTTP 200/503 without exposing credentials, IPs, or internal topology.

### Log Sanitization & Storage Security (LOG)

- [ ] **LOG-01**: Remove session IDs, auth tokens, and sensitive headers from `LogTenantRequests` middleware.
- [ ] **LOG-02**: Sanitize `NotificationService` logging to mask phone numbers, OTPs, student names, and message content.
- [ ] **LOG-03**: Refactor file uploads (`SaaSCheckoutController`, `PaymentController`, `StudentController`) from direct `public_path('uploads/...')` to tenant-isolated `Storage` disks.
- [ ] **LOG-04**: Secure private documents (student birth certificates, CNICs, fee bank slips) behind authorization-protected download controllers.

## v2 Requirements

### Advanced Monitoring & Multi-Region (V2)

- **V2-01**: OpenTelemetry tracing for multi-tenant database queries and queue job latencies.
- **V2-02**: Automated zero-downtime tenant database migration rollback pipeline.
- **V2-03**: S3-compatible cloud storage adapter (AWS S3 / Cloudflare R2) with tenant directory prefixing and presigned URL downloads.

## Out of Scope

| Feature | Reason |
|---------|--------|
| Complete Vue 3 frontend redesign | Project focus is architectural hardening, security, tenancy, and queues |
| Single-database row-level tenancy rewrite | Architecture is intentionally multi-database per tenant |
| Replacing Stancl Tenancy | `stancl/tenancy` v3 is already deeply integrated and meets requirements when configured properly |
| Changes to academic grading formulas | Domain business logic is stable; focus is authorization and data isolation |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| TEN-01 | Phase 1: Tenancy & Migration Architecture Audit | Complete |
| TEN-02 | Phase 1: Tenancy & Migration Architecture Audit | Complete |
| TEN-03 | Phase 1: Tenancy & Migration Architecture Audit | Complete |
| TEN-04 | Phase 1: Tenancy & Migration Architecture Audit | Complete |
| ENV-01 | Phase 2: Environment Configuration & Production Hardening | Complete |
| ENV-02 | Phase 2: Environment Configuration & Production Hardening | Complete |
| ENV-03 | Phase 2: Environment Configuration & Production Hardening | Complete |
| ENV-04 | Phase 2: Environment Configuration & Production Hardening | Complete |
| AUTH-01 | Phase 3: Authentication, Authorization & RBAC Hardening | Pending |
| AUTH-02 | Phase 3: Authentication, Authorization & RBAC Hardening | Pending |
| AUTH-03 | Phase 3: Authentication, Authorization & RBAC Hardening | Pending |
| SCOPE-01 | Phase 4: Identifier Scoping & IDOR Remediation | Pending |
| SCOPE-02 | Phase 4: Identifier Scoping & IDOR Remediation | Pending |
| SCOPE-03 | Phase 4: Identifier Scoping & IDOR Remediation | Pending |
| SCOPE-04 | Phase 4: Identifier Scoping & IDOR Remediation | Pending |
| QUEUE-01 | Phase 5: Asynchronous Queueing & Job Refactoring | Pending |
| QUEUE-02 | Phase 5: Asynchronous Queueing & Job Refactoring | Pending |
| QUEUE-03 | Phase 5: Asynchronous Queueing & Job Refactoring | Pending |
| QUEUE-04 | Phase 5: Asynchronous Queueing & Job Refactoring | Pending |
| HEALTH-01 | Phase 6: Safe Health-Check Endpoints | Pending |
| HEALTH-02 | Phase 6: Safe Health-Check Endpoints | Pending |
| HEALTH-03 | Phase 6: Safe Health-Check Endpoints | Pending |
| LOG-01 | Phase 7: Log Sanitization & Secure Storage Isolation | Pending |
| LOG-02 | Phase 7: Log Sanitization & Secure Storage Isolation | Pending |
| LOG-03 | Phase 7: Log Sanitization & Secure Storage Isolation | Pending |
| LOG-04 | Phase 7: Log Sanitization & Secure Storage Isolation | Pending |

**Coverage:**
- v1 requirements: 26 total
- Mapped to phases: 26
- Unmapped: 0 ✓

---
*Requirements defined: 2026-10-22*
*Last updated: 2026-10-22 after initial audit & scoping*
