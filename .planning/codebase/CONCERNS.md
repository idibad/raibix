---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# Codebase Concerns & Technical Debt

**Analysis Date:** 2026-10-22

This document catalogues technical debt, security vulnerabilities, architectural concerns, and performance bottlenecks discovered across the Laravel LMS application, categorized by the 8 audit focus areas.

---

## 1. Tenancy Architecture & Data Isolation

### Issue 1.1: Missing `CentralConnection` on Central Eloquent Models

- **Files Affected:**
  - `app/Models/SubscriptionPlan.php`
  - `app/Models/Coupon.php`
  - `app/Models/SaaSSetting.php`
  - `app/Models/SaaSActivityLog.php`
  - `app/Models/TenantSubscription.php`
- **Impact:** High.
  Only `Referral` and `SaaSTransaction` declare `use Stancl\Tenancy\Database\Concerns\CentralConnection;`. All other central models rely on the active default database connection. When referenced during tenant requests, developers are forced to manually wrap queries in `tenancy()->central(function () { ... })` (e.g. `BillingController.php:22`, `CheckMaintenanceMode.php:21`). If missed anywhere, the query attempts to query tables that only exist in the central database schema, throwing 500 SQL table not found exceptions.
- **Remediation:** Add `use CentralConnection;` trait to all models whose tables live strictly in the central database.

### Issue 1.2: Tenant Database Manager & Multi-DB Configuration for Staging/Production

- **Files Affected:** `config/tenancy.php`, `config/database.php`.
- **Impact:** High.
  `config/tenancy.php` defines `'central_connection' => env('DB_CONNECTION', 'central')`, but `config/database.php` has no explicit `central` connection defined. In local dev, SQLite is used and dynamically creates `database/tenant{id}.sqlite`. In staging/production (MySQL or PostgreSQL), dynamic tenant database creation requires `template_tenant_connection`, database creation grants, and prefix handling. Without separate connection templates, provisioning fails or shares central credentials.
- **Remediation:** Define explicit `'central'` and `'tenant_template'` connections in `config/database.php` and configure them in `config/tenancy.php`.

---

## 2. Environment Configuration Separation

### Issue 2.1: Missing Environment Configuration Split (Local, Staging, Production)

- **Files Affected:** `.env`, `.env.example`.
- **Impact:** Critical.
  Only a single monolithic `.env` exists with local development values (`APP_ENV=local`, `APP_DEBUG=true`, SQLite connection, database queue/session/cache).
- **Remediation:**
  - Create `.env.example`, `.env.staging.example`, `.env.production.example`.
  - Document required environment variables for production (Redis, MySQL/Postgres credentials, S3 storage keys, Stripe/JazzCash production secrets).

---

## 3. Production Configuration Hardening (APP_DEBUG, Cookies, Session, Cache)

### Issue 3.1: Development Security Settings & Leaky Session Cookies

- **Files Affected:** `config/session.php`, `config/cache.php`, `.env`.
- **Impact:** Critical.
  - `APP_DEBUG=true` in `.env`. If exposed in production, any unhandled exception renders full stack traces, environment variables, database credentials, and secret keys (Ignition error page).
  - `SESSION_SECURE_COOKIE` is null/false by default, meaning session cookies can be transmitted over unencrypted HTTP.
  - `SESSION_ENCRYPT` is false in `.env`.
  - `SESSION_DOMAIN` is null. In multi-tenant environments with subdomains, improper domain scoping can cause session collisions or cross-subdomain session leaks.
  - Session and Cache stores currently default to `database`. In production with high tenant traffic, hitting database tables for every session and cache read creates severe I/O bottlenecks.
- **Remediation:**
  - Enforce `APP_DEBUG=false` in production configuration.
  - Enforce `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, and `SESSION_SAME_SITE=lax`.
  - Migrate production session and cache drivers to Redis (`SESSION_DRIVER=redis`, `CACHE_STORE=redis`).

---

## 4. Authorization & RBAC Vulnerabilities

### Issue 4.1: Missing Policies and Route Authorization Middleware

- **Files Affected:**
  - `routes/tenant.php` (line 59: `Route::middleware(['auth'])->group(...)`)
  - `app/Policies/` (directory does not exist)
  - `app/Http/Controllers/Tenant/AdminPortalController.php`
  - `app/Http/Controllers/Tenant/StaffController.php`
  - `app/Http/Controllers/Tenant/FeeStructureController.php`
- **Impact:** Critical.
  All tenant routes are lumped inside a single `Route::middleware(['auth'])` group. There are **zero** role or permission middleware applied to admin or staff routes.
  A logged-in student or parent can navigate to `/admin/dashboard`, delete school classes (`DELETE /classes/{id}`), create notices, toggle user statuses (`/users/{id}/toggle-status`), or modify fee structures.
- **Remediation:**
  - Create role and permission middleware groups using `spatie/laravel-permission` (e.g. `role:Principal|Admin`, `role:Teacher`, `role:Student`, `role:Parent`).
  - Create Laravel Eloquent Policies (`FeeChallanPolicy`, `StudentPolicy`, `AssignmentPolicy`, etc.) to enforce object-level authorization.

---

## 5. Unscoped Global Identifiers (IDOR Vulnerabilities)

### Issue 5.1: Unscoped Direct Object Reference on Sensitive Endpoints

- **Files Affected:**
  - `FeeStructureController.php:323` (`printChallan($id)`)
  - `ParentPortalController.php:141` (`payChallan($challanId)`)
  - `ExamGradebookController.php:116` (`generateReportCard($examId, $studentId)`)
  - `StudentController.php:393` (`uploadDocument($id)`)
  - `SportsActivityController.php:150`
- **Impact:** Critical.
  Controllers query models directly by primary key without scoping to the authenticated user's student or children:
  ```php
  // FeeStructureController.php line 325:
  $challan = FeeChallan::with(['student.currentClass', 'transactions'])->findOrFail($id);
  ```
  Any authenticated parent or student can supply another student's ID or challan ID to inspect private financial statements, father CNICs, addresses, and academic report cards.
- **Remediation:** Scope all queries:
  ```php
  $challan = FeeChallan::whereHas('student.parent', function($q) {
      $q->where('user_id', auth()->id());
  })->findOrFail($id);
  ```

---

## 6. Synchronous Operations Requiring Background Queueing

### Issue 6.1: Heavy and Network-Bound Operations in HTTP Requests

- **Files Affected:**
  - `app/Http/Controllers/Tenant/FeeStructureController.php:207` (`generateMonthlyChallans`) - Iterates through all students synchronously in a single DB transaction.
  - `app/Http/Controllers/Tenant/ImportController.php` (`importStudents`, `importTeachers`, `importStaff`) - Synchronously parses CSVs, hashes passwords, provisions records, and assigns roles.
  - `app/Services/NotificationService.php` (`sendSms`, `sendWhatsapp`) - Makes blocking external HTTP calls to Twilio, UrduBit, and Meta Graph API inside request threads.
  - `app/Http/Controllers/Tenant/AdminPortalController.php:378` (`sendDefaulterReminder`) - Dispatches SMS and WhatsApp synchronously during button click.
  - `app/Services/AiVisionService.php` - Synchronous face recognition and NLP assignment evaluation.
- **Impact:** High.
  Causes request timeouts (504 Gateway Timeout), locks database tables during bulk generation, and degrades user experience.
- **Remediation:**
  - Implement queued jobs: `GenerateMonthlyFeeChallansJob`, `ImportStudentsJob`, `DispatchNotificationJob`, `ProcessAiVisionJob`.
  - Dispatch jobs using `dispatch()->onQueue('...')`.

---

## 7. Missing Production Health-Check Endpoints

### Issue 7.1: Lack of Robust, Safe Health-Check Endpoints

- **Files Affected:** `bootstrap/app.php`, `routes/web.php`.
- **Impact:** Medium.
  Only basic Laravel default `/up` route exists. There is no health endpoint that validates:
  - Central DB connectivity.
  - Redis / Cache availability.
  - Queue worker responsiveness.
  - Storage disk write permissions.
  Furthermore, health-check endpoints must not leak credentials, hostnames, IP addresses, or internal database names to unauthenticated probes.
- **Remediation:** Implement a clean `/health` or `/api/health` controller returning `{"status": "ok"}` with HTTP 200/503 without revealing sensitive internals.

---

## 8. Log Sanitization & Sensitive Data Exposure

### Issue 8.1: Sensitive Information Leaking into Application Logs

- **Files Affected:**
  - `app/Http/Middleware/LogTenantRequests.php:20` - Directly logs active session IDs: `Session: {$sessionId}`.
  - `app/Services/NotificationService.php:77, 117` - Logs full SMS and WhatsApp message bodies containing OTPs, fee amounts, and student names along with phone numbers.
- **Issue 8.2: Insecure File Uploads in `public_path()`**
- **Files Affected:**
  - `SaaSCheckoutController.php:117` (`public_path('uploads/onboarding')`)
  - `AdminPortalController.php:240, 429` (`public_path('uploads/renewals')`, `public_path('uploads/logos')`)
  - `PaymentController.php:31` (`public_path('uploads/fees')`) - Payment deposit slips containing bank account numbers and transaction IDs.
  - `StudentController.php:393` - Student birth certificates and ID documents moved directly into public docroot.
- **Impact:** Critical.
  Anyone with access to logs or any unauthenticated web visitor can view session tokens, sensitive PII, and private financial documents.
- **Remediation:**
  - Sanitize `LogTenantRequests` to strip session IDs.
  - Mask phone numbers and message content in `NotificationService`.
  - Refactor all file uploads to use `Storage::disk('local')` or protected S3 buckets, with signed URL or controller-mediated streaming for authorized users.

---

*Concerns analysis: 2026-10-22*
*Update as concerns are resolved or discovered*
