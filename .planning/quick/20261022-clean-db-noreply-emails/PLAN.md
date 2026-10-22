---
task: clean-db-noreply-emails
created_at: 2026-10-22T10:25:00+05:00
status: complete
type: quick
---

# Plan: Clean Database Clutter & Configure Password Reset and Alert Emails

## Context & Objectives
- **Clean the database**: 756 orphan test tenant SQLite database files (accumulated from PHPUnit test executions) occupy ~378MB in `database/`. We need an artisan command `tenants:clean-orphans` to safely purge all non-registered tenant databases while preserving the central database (`database.sqlite`) and all 21 active school tenant databases.
- **Configure password reset and alert emails**:
  - Configure `MAIL_FROM_ADDRESS="noreply@raibix.com"` and `MAIL_MAILER=log` in `.env` and `.env.example`.
  - Implement full tenant self-service and admin-triggered password reset flow (routes, controllers, Vue views `ForgotPassword.vue` and `ResetPassword.vue`, link in `Login.vue`, secure token storage in `password_reset_tokens`).
  - Configure system alert emails (`SchoolAlertMail`) for absence notices and fee reminders, ensuring all alert communications originate from `noreply@raibix.com`.

## Tasks

### Task 1: Database Cleanup & Orphan Tenant Purge
- Create `app/Console/Commands/CleanOrphanTenantDatabases.php` (`tenants:clean-orphans`).
- Execute `php artisan tenants:clean-orphans --force` to purge orphan files.
- Verify active tenant SQLite files and central database remain intact.

### Task 2: Email Configuration in Environment Files
- Update `.env`: set `MAIL_FROM_ADDRESS="noreply@raibix.com"`, `MAIL_MAILER=log`, `MAIL_FROM_NAME="${APP_NAME}"`.
- Update `.env.example`: set `MAIL_FROM_ADDRESS="noreply@raibix.com"`.
- Verify `config/mail.php` defaults.

### Task 3: Tenant Password Reset Implementation
- Add tenant password reset routes in `routes/tenant.php`.
- Add `showForgotPasswordForm`, `sendResetLinkEmail`, `showResetPasswordForm`, and `resetPassword` to `TenantAuthController.php`.
- Create `resources/js/Pages/Tenant/Auth/ForgotPassword.vue` and `ResetPassword.vue`.
- Update `resources/js/Pages/Tenant/Auth/Login.vue` link to point to `/forgot-password`.
- Update `StaffController@sendResetLink` to persist token in `password_reset_tokens` and dispatch `ResetPasswordMail`.
- Update `ResetPasswordMail.php` with explicit envelope from address `noreply@raibix.com`.

### Task 4: Configure Alert Emails
- Create `app/Mail/SchoolAlertMail.php` with responsive design and school branding.
- Update `SendFeeReminders.php` to dispatch `SchoolAlertMail` with subject and formatted fee details.
- Update `NotificationService.php` to dispatch email absence alerts to parents when email is present.

### Task 5: Automated Verification & Test Coverage
- Add test in `tests/Feature/PasswordResetAndEmailAlertTest.php` testing:
  - Orphan cleaner command.
  - Password reset link generation & password update.
  - Alert email dispatch with `noreply@raibix.com`.
- Run PHPUnit test suite.
