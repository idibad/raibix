---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# Coding Conventions

**Analysis Date:** 2026-10-22

## Code Style & Formatting

- **Standards:** Compliant with PSR-12 and standard Laravel formatting rules.
- **Linter & Code Fixer:** Laravel Pint (`vendor/bin/pint`) configured in `composer.json`.
- **Type Safety:** Strict typing (`declare(strict_types=1);`) used in routes and newer tenant controllers. Method arguments and return types are strongly encouraged for all service and controller methods.

## Architecture & Tenancy Conventions

### Multi-Tenancy Data Scoping

1. **Tenant-Aware Queries:**
   - In multi-database mode, queries to tenant models automatically target the tenant's SQLite/MySQL database once `InitializeTenancyByDomain` boots.
   - **Critical Rule:** When querying models belonging to the central database from within a tenant request or controller (e.g. `SubscriptionPlan`, `Coupon`, `SaaSSetting`), the model **must** use `Stancl\Tenancy\Database\Concerns\CentralConnection`. Relying on manual ad-hoc `tenancy()->central(...)` closures is error-prone.
2. **Global Identifier Scoping:**
   - Controller methods accepting route parameters (e.g. `/{id}`) must verify ownership against the authenticated context (`Auth::user()`) or parent relationship rather than calling `Model::findOrFail($id)` blindly.
   - For example: `FeeChallan::where('student_id', $student->id)->findOrFail($id)` instead of `FeeChallan::findOrFail($id)`.

### Authorization & RBAC

- **Roles:** Configured via `spatie/laravel-permission` with guard `'web'`.
- **Expected Roles:** `Super Admin` (Central), `Principal` / `Admin`, `Teacher`, `Student`, `Parent` / `Guardian`, `Accountant`, `Librarian` (Tenant).
- **Enforcement Pattern:**
  - Route groups should apply role/permission middleware (e.g. `role:Admin|Principal` or `can:...`).
  - Sensitive controller actions should invoke authorization policies (e.g. `$this->authorize('view', $challan);`).

### Service Layer Conventions

- Controllers should delegate business workflows (provisioning, fee processing, admissions, notifications, payment gateway interactions) to dedicated classes in `app/Services/`.
- Services must return structured results or typed models rather than directly rendering HTTP responses.

### Background Queue Conventions

- Time-consuming, bulk, or network-bound tasks must implement `Illuminate\Contracts\Queue\ShouldQueue` and be dispatched to the queue:
  - Bulk monthly fee challan generation.
  - Student, teacher, and staff CSV imports.
  - External SMS and WhatsApp gateway requests.
  - AI vision image processing and face recognition.
- Queued jobs running on behalf of a tenant must ensure tenant context is bootstrapped via `Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper` or implement `Stancl\Tenancy\Contracts\TenantAwareJob`.

### File Storage Conventions

- Uploaded files must use Laravel's `Storage` facade with tenant-aware disks (`Storage::disk('public')->putFile(...)` or `Storage::disk('local')->putFile(...)`).
- Direct file movement into `public/uploads/` via `$file->move(...)` violates multi-tenant isolation and security requirements.
- Private student documents (e.g. B-forms, CNICs, academic records) must be stored on non-public disks and served via authorization-protected download controllers.

### Logging Conventions

- Avoid logging raw session IDs, auth tokens, passwords, credit card details, or sensitive PII (student phone numbers, parent CNICs).
- Sanitize or mask message bodies in notification logs.

---

*Conventions analysis: 2026-10-22*
*Update after style changes*
