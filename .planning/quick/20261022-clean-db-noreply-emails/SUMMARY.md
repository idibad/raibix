---
task: clean-db-noreply-emails
completed_at: 2026-10-22T11:17:00+05:00
status: complete
type: quick
---

# Summary: Clean Database Clutter, Configure Password Reset & Alert Emails, and Normalize UI Placeholders

## Execution Overview
Addressed three core requirements:
1. **Database Purge**: Cleaned up 756 orphan test tenant SQLite database files (~378MB) while safely preserving the central database (`database.sqlite`) and all 21 registered school tenant databases.
2. **Email System Configuration**:
   - Set up `noreply@raibix.com` across `.env`, `.env.example`, and `config/mail.php` with `MAIL_MAILER=log`.
   - Built an end-to-end self-service and staff-triggered password reset workflow for school tenants.
   - Built a branded, responsive `SchoolAlertMail` mailable for student absence notifications and fee reminders.
3. **UI Placeholder Normalization**: Replaced all hardcoded example school names (e.g., "Beaconhouse", "City School", "Lahore Grammar School") and student/staff mock names with clean, descriptive input labels and generic placeholders across central and tenant views.

---

## Key Changes Implemented

### 1. Database Cleanup Command (`app/Console/Commands/CleanOrphanTenantDatabases.php`)
- Implemented `tenants:clean-orphans` supporting `--dry-run`, `--force`, and `--path=`.
- Safely queries active registered tenant IDs directly from the central database connection.
- Identified and deleted 756 orphan SQLite files in `database/`.
- Preserved all 21 registered tenants (`108018`, `beaconhouse`, `benchmark`, `darearqam`, `safarischoolsystem`, etc.) and `database.sqlite`.
- Added `tenanttest*` to `database/.gitignore` to prevent test-generated SQLite files from being tracked by git.

### 2. Password Reset Flow
- **Routes (`routes/tenant.php`)**:
  - `GET /forgot-password` (`tenant.password.request`)
  - `POST /forgot-password` (`tenant.password.email`)
  - `GET /reset-password/{token}` (`tenant.password.reset`)
  - `POST /reset-password` (`tenant.password.update`)
- **Controller (`app/Http/Controllers/Tenant/TenantAuthController.php`)**:
  - Implemented `showForgotPasswordForm`, `sendResetLinkEmail`, `showResetPasswordForm`, and `resetPassword`.
  - Generates secure random 64-character tokens stored with SHA-256 hashes in `password_reset_tokens` with 60-minute expiration.
  - Updates user credentials and invalidates used reset tokens.
- **Frontend Views**:
  - Created `resources/js/Pages/Tenant/Auth/ForgotPassword.vue`: Clean, accessible form to request reset links.
  - Created `resources/js/Pages/Tenant/Auth/ResetPassword.vue`: Token-validated form for new password and confirmation.
  - Updated `resources/js/Pages/Tenant/Auth/Login.vue`: Updated "Forgot password?" link, added flash notification display, cleaned input placeholders.
- **Staff Administration (`app/Http/Controllers/Tenant/StaffController.php`)**:
  - Updated `sendResetLink` to generate real tokens in `password_reset_tokens` and dispatch `ResetPasswordMail`.
- **Mailable (`app/Mail/ResetPasswordMail.php`)**:
  - Configured envelope to use `noreply@raibix.com` with school name as display name.

### 3. Alert Emails (`app/Mail/SchoolAlertMail.php`)
- Created a reusable, styled HTML alert template for tenant notifications.
- Integrated into `app/Console/Commands/SendFeeReminders.php` for automated fee payment reminders.
- Integrated into `app/Services/NotificationService.php` (`sendAbsenceAlert`) to email student absence alerts directly to parent contact emails.

### 4. Placeholder & Field Label Normalization
Replaced hardcoded real school names and sample entity names with standard field labels:
- `resources/js/Pages/Central/Landing.vue`: Cleared default `beaconhouse` ref, cleaned contact form placeholders.
- `resources/js/Pages/Central/Checkout.vue` & `CheckoutSuccess.vue`: Cleaned school name and credentials placeholders.
- `resources/js/Pages/Central/Dashboard.vue`: Replaced "Beaconhouse" and "beacon" subdomain mocks with generic labels.
- `resources/js/Pages/Errors/TenantUnavailable.vue`: Replaced "Beaconhouse, City School..." search text with descriptive label.
- `resources/js/Pages/Tenant/Admissions/Index.vue`: Replaced "Beaconhouse School System", "Kamran Khan" with field labels.
- `resources/js/Pages/Tenant/Landing.vue`: Replaced student/parent example names with field labels.
- `resources/js/Pages/Tenant/Admin/Dashboard.vue` & `Teacher/Dashboard.vue`: Cleaned timetable, exam, section, study material, and activity placeholders.
- `resources/js/Pages/Tenant/Admin/ERP/`: Cleaned Transport, Hostels, Inventory, Settings, and Certificate templates.
- `resources/js/Pages/Tenant/Admin/Chat.vue`, `Parent/Chat.vue`, `Teacher/Chat.vue`: Cleaned message subject placeholders.

---

## Verification
- Created comprehensive test suite in `tests/Feature/PasswordResetAndEmailAlertTest.php` covering:
  1. `CleanOrphanTenantDatabases` execution and active file preservation.
  2. Public tenant forgot-password link request and `ResetPasswordMail` queuing from `noreply@raibix.com`.
  3. Form submission of password reset with valid token and subsequent password update.
  4. Token expiration rejection after 60 minutes.
  5. Staff admin triggering reset password email.
  6. Absence alert email dispatch to parent via `SchoolAlertMail` from `noreply@raibix.com`.
- Test result: **6 tests, 21 assertions, 100% passing**.
