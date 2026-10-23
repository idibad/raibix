---
task: redesign-raibix-dashboards
created_at: 2026-10-23T10:28:00+05:00
completed_at: 2026-10-23T11:02:00+05:00
status: completed
type: quick
---

# Plan: Raibix Unified Multi-Role Dashboard Redesign

## Objectives
Redesign all four Raibix tenant dashboards (Admin/Principal, Teacher, Student, Parent) to feel like components of a single, premium, minimal, intelligent education operating system. Incorporate the Raibix visual identity (Poppins bold typography, white/near-white foundation `#FFFFFF` / `#F8F9FA`, dark charcoal text `#0B0D14`, subtle `#E5E7EB` borders, and the blue-violet Intelligence Core dot `linear-gradient(135deg, #8B5CF6, #2F6BFF)`).

Enforce strict backend and frontend RBAC for all roles (Principal, Accountant, Librarian, Teacher, Student, Parent).

## Tasks

### Task 1: Shared Design System & Layout Foundation
- Update `HandleInertiaRequests.php` to share `academic_session` across all tenant routes.
- Build `resources/js/Layouts/TenantAppLayout.vue`:
  - Compact collapsible sidebar with Lucide/FontAwesome icons, tooltips, active states with blue-violet dot.
  - Shared top navigation with tenant logo, name, academic session chip, notifications, and profile menu.
  - Mobile responsive drawer with smooth animations.
  - Standardized page header, breadcrumbs, and right-aligned actions slot.
- Update `resources/js/Layouts/TenantAdminLayout.vue` to align seamlessly with the new design system for ERP sub-pages.

### Task 2: Backend RBAC Hardening for Roles (Principal, Accountant, Librarian, Registrar)
- Update `routes/tenant.php` with granular role middleware on admin sub-routes:
  - Library: `tenant.role:admin,principal,librarian`
  - Finance, HR/Payroll, Fee structures: `tenant.role:admin,principal,accountant`
  - Admissions, Student management, Classes: `tenant.role:admin,principal,registrar,receptionist`
  - System settings, platform subscription: `tenant.role:admin,principal`
- In `AdminPortalController.php`, adapt dashboard data fetching and tab permissions to strictly match the authenticated user's role.

### Task 3: Admin / Principal Institutional Command Center Redesign
- Redesign `resources/js/Pages/Tenant/Admin/Dashboard.vue`:
  - Clean institutional header with academic session and quick status badge.
  - Action items banner: pending manual fee approvals, staff/student leave approvals, admissions inquiries.
  - Real operational metric cards (Active Students, Staff Roster, Attendance %, Fee Collection Target vs Collected).
  - Quick action toolbar (Enroll, Notice, Fees, Defaulters).
  - Grouped navigation tabs (Overview, People, Academics, Attendance, Finance, Approvals, Notices, Settings).
  - Clean data tables with search filters, pagination, status chips, and empty states.
  - Preserve all existing modals, forms, and workflows without data loss.

### Task 4: Teacher Workspace Redesign
- Redesign `resources/js/Pages/Tenant/Teacher/Dashboard.vue`:
  - Integrate with `TenantAppLayout.vue`.
  - Today's timetable with next upcoming class period countdown.
  - Pending action cards: pending assignment submissions to grade, leave requests to approve.
  - Clear tabs: Timetable, My Classes, Assignments & AI Grading, Study Materials (LMS), Gradebook & Exams, Observations, Leaves, Notices.
  - Quick actions: Take Attendance, Create Assignment, Upload Material.

### Task 5: Student Learning Space Redesign
- Redesign `resources/js/Pages/Tenant/Student/Dashboard.vue`:
  - Integrate with `TenantAppLayout.vue`.
  - Daily schedule & next class period card.
  - Upcoming assignments & due dates with status pills (Pending, Submitted, Overdue).
  - Workspaces: Timetable, Assignments (with upload & Q&A), Study Notes, Exam Results & GPA, Attendance & Leaves, Certificates.
  - Strict data isolation ensuring only own records are visible.

### Task 6: Parent Family & Student Progress Redesign
- Redesign `resources/js/Pages/Tenant/Parent/Dashboard.vue`:
  - Integrate with `TenantAppLayout.vue`.
  - Child switcher bar with quick status (attendance today, pending fees, upcoming exams).
  - Workspaces: Overview, Fee Statements & Online Payment, Academic Progress & Report Cards, Attendance Logs, Leave Applications, Notices & PTMs.
  - Calming, trustworthy visual tone with plain-language summaries.

### Task 7: Compilation, RBAC Test Coverage & Verification
- Compile frontend with `npm run build` and ensure zero errors.
- Add test coverage in `tests/Feature/AuthorizationRBACSecurityTest.php` for librarian and accountant module restrictions.
- Run full test suite (`php artisan test`) and verify 100% pass rate.
