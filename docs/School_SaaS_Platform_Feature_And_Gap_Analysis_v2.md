# School SaaS Platform — Full Dashboard, Feature & Gap Analysis (v2)

**Stack:** Laravel 11 · Stancl/Tenancy (multi-database, one DB per school) · Vue 3 + Inertia.js  
**Domains:** Central Platform Domain (Super Admin) + Tenant School Domains (Admin, Teacher, Student, Parent)

This v2 analysis keeps every feature from the original breakdown, expands each one with implementation detail, and adds a **🚩 Missing / Broken Flow & Resolution Status** block after every dashboard calling out what's referenced-but-not-built, what's inconsistent between roles, and what a production school-SaaS product requires. Additionally, it highlights our newly implemented **Real-Time School Branding & Logo Customization** system (`/admin/settings/logo`). A consolidated **cross-platform gap list** is provided at the end.

---

## 1. ⚡ Super Admin Dashboard — `Central/Dashboard.vue`

**Audience:** Platform owners, cloud DevOps, SaaS billing admins.  
**Purpose:** Tenant provisioning, database lifecycle, subscription/plan config, and platform-wide telemetry.

### Expanded Feature Breakdown & Implementation Details

1. **Tenant Provisioning Engine (`Stancl\Tenancy`)**
   - **Lifecycle Management:** Creates isolated MySQL databases per school (`tenant_{id}`), runs migrations, and seeds initial roles/classes upon subscription activation.
   - **Domain Mapping:** Assigns custom domains or subdomains (`{school}.lmscloud.test` or custom FQDN via `Domain` model attached to `Tenant`).
2. **SaaS Plan & Subscription Tier Configuration**
   - **Plan Management:** Defines limits for students, storage quotas (e.g., 5GB vs 50GB for lecture materials), and feature toggles (e.g., SMS alerts, AI timetable generator).
   - **Billing & Revenue Telemetry:** Aggregates MRR (Monthly Recurring Revenue), active subscriptions, churn rate, and pending payment verifications across all tenant schools.
3. **Global System Health & Database Monitoring**
   - **Tenant Status Toggles:** Instantly suspend (`status = 'suspended'`) or activate (`status = 'active'`) tenant schools. Suspended tenants are blocked at the `HandleInertiaRequests` middleware level across all subdomains.
   - **Error & Queue Tracking:** Monitors background queue workers (`redis` / `database` queues) for tenant migrations, bulk email/SMS dispatches, and JazzCash/Stripe webhook ingestions.

### 🚩 Missing / Broken Flow & Resolution Status
- **Webhook Authentication & Security Gap:** The JazzCash IPN (`/ipn/jazzcash`) webhook endpoint lacks cryptographic HMAC signature validation, exposing the platform to unauthorized payment confirmation spoofing.
- **Automated Backup / Restore UI:** While `stancl/tenancy` supports tenant database operations, there is currently no Super Admin UI to trigger point-in-time snapshots or export single-tenant SQL dumps for school administrators upon request.
- **Plan Enforcement Hooks:** Storage and student count limits are stored in subscription models but not actively enforced inside the `Tenant` file upload or admission inquiry controllers when a tenant reaches 100% quota.
- **Dunning Management:** No automated retry logic, grace period handling, or auto-suspension workflows when a school's subscription payment fails or card expires.
- **Usage-Based Billing & Metering:** Lacks logic to charge or scale pricing dynamically based on metered usage (e.g., active student count, extra storage space).
- **Self-Serve Trials & Upgrades:** No self-serve trial-to-paid conversion flow, nor UI for school admins to upgrade/downgrade their plans autonomously.
- **Coupons & Referral Programs:** Absence of promotional code logic or referral tracking essential for SaaS onboarding and marketing campaigns.
- **Custom Domain Mapping & White-Labeling:** Tenants are restricted to subdomains. No mechanism exists to map custom FQDNs (e.g., `school.com`) or white-label the platform for resellers.
- **Tenant Impersonation:** Super Admins cannot securely "login as" a specific school admin to debug issues or provide guided support without requesting credentials.
- **Central-Level Audit Logging:** No centralized audit trail tracking Super Admin actions (e.g., who changed a tenant's plan or suspended a school) across the platform.
- **Tenant API Rate Limiting:** Missing rate-limiting middleware configured per tenant to prevent a single noisy school from degrading performance for others on the central platform.
- **Data Export & Tenant Offboarding:** No defined flow for GDPR-compliant data deletion or structured data export (SQL/CSV dumps) when a school cancels their subscription.

---

## 2. 🏛️ Principal & School Admin Portal — Complete Day-to-Day Operational Suite (`Tenant/Admin/Dashboard.vue`)

**Audience:** School Principals, Headmasters, Registrar, Accountants, Librarians, Receptionists & Administrative Staff.  
**Purpose:** Daily school governance, comprehensive staff/student lifecycle management, timetable and curriculum scheduling, financial operations and fee structure reconciliation, exam grading oversight, and institutional profile customization.  
**Scope Notice:** This comprehensive specification replaces the narrow `"Overview / Classes / Overdue Fees / Notice Board / Admissions"` scope with what a Principal or School Admin actually needs to run a school day-to-day. Features are strictly prioritized into **P0 (broken without it)**, **P1 (expected by any real school admin)**, and **P2 (valuable, can phase in later)**.

---

### P0 — Core Gaps (The Portal Does Not Function as an "Admin" Tool Without These)

1. **User Management (`/admin/users`) — Role-Based Staff & Account Lifecycle (`StaffController`)**
   - **+ Add User Modal/Form:** Features a dynamic **Role selector** targeting `Teacher`, `Admin/Vice Principal`, `Registrar`, `Accountant`, `Front Desk/Receptionist`, `Librarian` (plus `Student` and `Parent` when created manually outside admissions).
   - **Dynamic Role-Specific Fields:** Selecting `Teacher` instantly reveals specialized inputs for subjects taught (`Subject` model pivot), academic/professional qualifications (`B.Ed`, `M.Sc`, `M.Phil`), and assigned homeroom section. Non-teaching roles (e.g., `Accountant` or `Front Desk`) dynamically hide teaching inputs and show only relevant employment and access control fields.
   - **Edit User Operations:** Full capability to modify staff demographic details, contact numbers, assigned roles, specialized subject bindings, and homeroom section allocations (`StaffController@update`).
   - **Deactivate / Reactivate User Status Toggle:** A soft status toggle (`status = 'active' | 'deactivated'`) instead of deletion. This ensures historical records (attendance marked, exam grades entered, fee challans issued) remain fully intact while immediately barring the account from logging in via `TenantAuthController` middleware.
   - **Hard Delete with Audit Safeguards:** Hard deletion (`StaffController@destroy`) is strictly gated behind a confirmation modal + mandatory `reason` text field. To maintain database integrity, hard delete is permitted **only** for accounts with zero attached historical records (`$user->hasNoHistoricalData()`).
   - **Admin-Triggered Password Reset (`StaffController@sendResetLink`):** Generates and dispatches a secure, time-sensitive reset link via email or SMS. Administrators never see or set raw passwords directly, maintaining enterprise credential hygiene.
   - **Invite via Email/SMS Onboarding:** Dispatches account invitations (`UserInvitation` model) with "Set Your Password" secure URLs instead of manual password relaying.
   - **Bulk Import Users via CSV (`StaffController@bulkImport`):** Critical start-of-school-year onboarding engine using `Maatwebsite/Laravel-Excel`. Allows rapid batch ingestion of teachers and administrative staff with automated row validation and duplicate detection.
   - **Searchable User Directory & Filter Grid:** Interactive data grid filterable by role, active status, department, and subject assignment.
   - **Telemetry & Last Login Activity Indicator:** Tracks and displays `last_login_at` and active session timestamps per user, allowing administrators to immediately spot dormant or unactivated accounts.

2. **Student Management (`/admin/students`) — Full CRUD, Status Lifecycle & Document Vault (`StudentController`)**
   - **+ Add Student Manual Enrollment (`StudentController@store`):** Direct enrollment workflow bypassing the inquiry/application pipeline for mid-year transfers, sibling enrollments, or executive admissions.
   - **Comprehensive Edit Capabilities:** Update personal demographic info, guardian/parent linking (`parent_id`), assigned `SchoolClass` and `Section` (`section_id`), official roll number (`roll_no`), and unique admission number (`admission_no`).
   - **Remove Student (Soft-Delete & Archive):** Strictly soft-deletes (`deleted_at` timestamp / `status = 'archived'`) to ensure all attached academic records, attendance history, and financial challans remain preserved for compliance.
   - **Defined Student Status Lifecycle (`student_status` enum field):**
     - `Active`: Normal enrolled and attending student.
     - `On Leave`: Approved temporary absence (e.g., extended medical leave, sabbatical).
     - `Suspended`: Disciplinary status, temporarily barred from campus and portal login.
     - `Transferred Out`: Left for another school mid-session.
     - `Graduated`: Successfully completed the final grade level.
     - `Expelled`: Permanently removed from school rolls for severe infractions.
     - `Inactive/Dropped`: Stopped attending without formal withdrawal notification.
   - **Immutable Status Transition Audit Log (`StudentStatusLog` model):** Every status transition automatically creates a permanent audit record logging **who changed the status (`changed_by_user_id`), the exact timestamp (`created_at`), the previous and new states, and a mandatory short reason string (`reason`)**. This log is permanently visible inside the student's profile.
   - **Section Transfer & Promotion (`StudentController@transferSection`):** One-click transfer to move a student between sections (e.g., `10-A → 10-B`) or promote between classes with instant roster synchronization.
   - **End-of-Year Bulk Promotion Tool (`StudentController@bulkPromote`):** Promotes an entire class/section to the next grade level (`Class 9 → Class 10`) in a single atomic database transaction. Includes an interactive **Exception Grid** to uncheck individual students who repeat or hold back.
   - **Sibling Linking Engine (`FamilyAccessCode`):** Connects multiple student profiles to a single consolidated `Parent` account (`users.id` where `user_type = 'parent'`), enabling seamless multi-child switching in the Parent portal.
   - **Student Document Vault (`StudentDocument` model):** Secure cloud upload, preview, and replacement for birth certificates, previous school leaving certificates (`SLC`), medical immunization records, and ID photos (`public/uploads/tenants/{id}/students/{student_id}/docs/`).
   - **Full Searchable Student Directory:** Complete filterable directory across all enrolled classes and sections by status, admission year, name, and roll number (replacing current partial views).

3. **Reworked Admissions Flow (`/admin/admissions`) — Pipeline & Multi-Source Ingestion (`AdmissionController`)**
   - **Multi-Source Inquiry Ingestion:**
     - *(a) Public Embeddable Web Form:* Public REST endpoint (`POST /api/v1/inquiries`) embedded directly on the school's external marketing website (`{school}.edu.pk`).
     - *(b) Front-Desk Manual Entry:* Walk-in and telephone inquiry logging form (`AdmissionController@storeInquiry`) used by front-desk receptionists. Both channels feed into one unified admissions pipeline.
   - **Interactive Admissions Pipeline (Kanban & Stage-List View):** Visual progression tracking through distinct stages: `Inquiry Submitted` → `Test Scheduled` → `Tested / Evaluated` → `Merit List Ranked` → `Approved by Principal` → `Enrolled / Rejected / Waitlisted`.
   - **Admission Settings & Seat Capacity Configuration (`AdmissionSetting` model):** Open or close admission windows per grade level, set strict seat capacities (`max_seats`) per class/section, and configure non-refundable application/testing fee charges (`PKR`).
   - **Automated Waitlist Management:** Automatically queues and ranks over-subscribed candidates, triggering notification prompts when seats open up due to cancellations or transfers.
   - **Applicant Communication Log (`AdmissionCommunication` model):** Centralized chronological log of every phone call, SMS reminder, and email sent to the candidate's family. Ensures all administrative staff have complete historical visibility without asking around.
   - **Automated First Fee Challan Generation on Enrollment (`FeeChallanController@generateAdmissionChallan`):** When an applicant is transitioned to `Enrolled` (`AdmissionController@enroll`), the system **automatically creates the student account AND generates the first admission/tuition fee challan**, fixing the broken flow where the Parent portal expected challans that nothing created.

---

### P1 — Expected by Any Real School Admin (High Priority Operational Suite)

4. **Class, Section & Subject Management (`/admin/academic/classes`)**
   - Create, edit, and delete `SchoolClass` (grade level) and `Section` records (`10-A`, `10-B`) with configured capacity ceilings (`max_capacity`).
   - Assign a designated **Class Teacher (Homeroom Teacher)** per section (`section.homeroom_teacher_id`).
   - Create and manage `Subject` records (`Subject` model: code, credit hours, type: core/elective) and assign them to specific classes + teaching faculty (`SubjectTeacher` pivot).
   - Class-wise subject matrix feeding directly into the timetable schedule and exam gradebook.

5. **Timetable Builder & Conflict Detection Engine (`/admin/academic/timetable`)**
   - **Weekly Grid per Section (`TimetableSlot` model):** Interactive drag-and-drop grid assigning `Subject + Teacher + Room + Time Slot` across Monday–Saturday.
   - **Automated Conflict Detection Engine:** Real-time validation warning if a teacher or physical classroom (`Room` model) is double-booked across different sections during the exact same period.
   - **Publish / Unpublish Controls:** Draft mode vs. published state. Publishing instantly synchronizes the schedule to the Teacher's "My Timetable" (`Tenant/Teacher/Dashboard.vue`) and Student's schedule view.

6. **Fee Structure & Challan Generation Engine (`/admin/finance/fees`)**
   - **Configurable Fee Heads (`FeeHead` model):** Define customized fee components (Tuition Fee, Transport Fee, Examination Fee, Admission Fee, Laboratory/Misc. Charges) per class level or customized per student.
   - **Bulk Challan Generation (`FeeChallanController@bulkGenerate`):** Monthly or term-wise bulk challan creation for an entire class or section executed via background queue (`GenerateMonthlyChallansJob`).
   - **Fee Concessions & Scholarships (`FeeConcession` model):** Assign percentage-based (`%`) or fixed amount (`PKR`) waivers per student with mandatory reason notes and Principal approval workflows.
   - **Manual Payment Recording & Ledger Reconciliation (`PaymentReceipt` model):** Front-desk cash/cheque receipt entry, automatically reconciling against the online `JazzCash` / bank digital payment ledger.
   - **Automated Defaulter Reminders (`SendDefaulterRemindersJob`):** One-click or scheduled bulk SMS/WhatsApp/Email reminders targeting parents of overdue accounts (`due_date < today()`).
   - **Refunds, Adjustments & Audit Trail:** Immutable financial ledger logging adjustments, write-offs, and fee refunds.

7. **Staff Management — Teacher & Faculty Specific (`/admin/staff/teachers`)**
   - Assign subjects and teaching classes to individual faculty members.
   - Set **Substitute Teachers** (`SubstituteAssignment` model) when assigned homeroom or subject teachers are absent or on approved leave.
   - **Staff Leave Request + Approval Workflow (`StaffLeaveRequest` model):** Teachers submit leave applications via their portal; Principal reviews, approves/rejects, and triggers automated timetable substitute prompts.
   - **Dedicated Staff Attendance Tracking (`StaffAttendance` model):** Biometric or manual daily check-in/check-out grid specifically for teaching and administrative employees (completely distinct from student attendance).
   - **Faculty Workload Analytics View:** Graphical workload bar chart displaying assigned periods per week (`periods_assigned` vs. `target_workload`) to catch over-loaded or under-utilized teachers.

8. **Notice Board — Full Publishing Control (`/admin/notices`)**
   - **+ Create Notice Modal (`NoticeController@store`):** Full rich-text creation modal, resolving the previous gap where Admin could only view notices.
   - **Full Lifecycle Management:** Edit, delete, and unpublish active notices (`NoticeController@update` / `destroy`).
   - **Precision Audience Targeting:** Select `All`, `Teachers Only`, `Students Only`, `Parents Only`, or target specific `Class/Section` audiences.
   - **Scheduled Publishing & Urgent SMS Push:** Set future `publish_at` timestamps and toggle `[x] Send Urgent SMS/WhatsApp Notification` for emergency closures or urgent exam schedules.
   - **School Event Calendar (`SchoolEvent` model):** Dedicated event calendar distinct from notices for exams, national holidays, parent-teacher meetings (PTMs), and sports days.

9. **Exams & Report Cards Engine (`/admin/examinations`)**
   - Define exam/term schedules (`ExamTerm`) and custom grading schemes (`GradingScheme` with mark cutoffs, GPA, and letter grade bands `A+`, `A`, `B`, `C`, `F`).
   - Enter or bulk-import subject marks (`ExamResult` model) via CSV or web grid per class and subject.
   - **Automated Report Card Generation (`ReportCardController@generate`):** Combines exam marks, GPA calculation, teacher remarks, and attendance percentages into printable/downloadable PDF report cards (`barryvdh/laravel-dompdf`).
   - **Promotion Status Flagging:** Automatically flags students as `Recommended for Promotion` vs. `Hold Back / Academic Review` based on final term GPA, feeding into the Bulk Promotion tool.

10. **Reports & Executive Analytics (`/admin/reports`)**
    - **Enrollment Trends:** Historical multi-year student enrollment growth charts across grade levels.
    - **Attendance Analytics:** Daily, monthly, and section-wise student attendance trends highlighting chronic absenteeism.
    - **Financial Collection vs. Outstanding:** Real-time cash flow summary comparing expected fee revenue vs. collected vs. defaulter balance.
    - **Admissions Funnel Conversion:** Conversion analytics from `Inquiry` → `Tested` → `Approved` → `Enrolled`.
    - **Board-Ready Exports:** One-click CSV and PDF export capability across all reports for board meetings and parent-teacher consultations.

---

### P2 — Valuable, Can Phase In Later (Advanced Institutional Scaling)

11. **Granular Roles & Permissions Matrix (`/admin/settings/roles`)**
    - Custom role definitions (`Spatie/Laravel-Permission`) beyond fixed defaults (e.g., Vice Principal can approve admissions but cannot edit fee structures; Accountant can record payments but cannot touch exam grades).
    - Interactive Permission Matrix grid editable by the Principal.

12. **School Profile, Academic Year & Template Settings (`/admin/settings/profile`)**
    - **✨ NEWLY IMPLEMENTED: School Branding & Logo Customization (`/admin/settings/logo`):** Upload official crest (`AdminPortalController@updateLogo`) shared globally across login screens (`Tenant/Auth/Login.vue`) and all 4 portal headers (`$page.props.tenant.logo`). Includes interactive preview and one-click removal (`removeLogo`).
    - **Academic Year / Session Management (`AcademicSession` model):** Create new academic sessions (`2026-2027`), archive completed sessions, and toggle which session's data is currently "active" across all dashboards.
    - **Holiday Calendar Configuration:** Define academic holidays that automatically block attendance marking on those dates.
    - **Dynamic Document Templates:** Configurable templates for Bonafide Certificates, School Leaving Certificates (`SLC`), and Student/Staff ID Cards that auto-fill dynamically from student/user records.

13. **Library Management Module (`/admin/library`)**
    - Book cataloging, ISBN barcode tracking, issue/return logs (`BookIssue` model), and automated overdue fine calculation linked to fee challans.

14. **Transport Management Module (`/admin/transport`)**
    - Bus route definitions, driver and vehicle assignments (`BusRoute`, `Vehicle`), student-to-route assignment, and optional GPS live tracking integration.

15. **Inventory & Asset Management (`/admin/inventory`)**
    - Institutional asset tracking for classroom furniture, computer lab hardware, science laboratory equipment, and maintenance audit logs.

---

### 🚩 Quick Answer & Resolution Status for Direct Queries & Core Gaps

| Question / Identified Core Gap | Real-World School Admin Reality & Resolution Status |
|---|---|
| **1. "There's no Add User by role option"** | **Confirmed Real Gap (`P0 #1`).** Resolved in architecture by introducing `StaffController@store` with a role selector (`Teacher`, `Admin/Vice Principal`, `Registrar`, `Accountant`, `Front Desk`, `Librarian`), dynamic role-specific fields, edit/deactivate status toggles (instead of hard delete), secure admin-triggered password reset links, bulk CSV import (`Laravel-Excel`), and a filterable staff directory. |
| **2. "Why would Admin add an admission inquiry?"** | **Confirmed Architectural Misalignment (`P0 #3`).** Manual inquiry logging shouldn't be the primary path for a Principal. Inquiries originate from: (a) a public web form embedded on the school site, and (b) front-desk receptionist entry for walk-ins/calls. The Admin/Principal's role is reviewing and progressing the admissions pipeline (`Kanban view: Test → Merit → Enroll`), managing waitlists, and overseeing communication logs. |
| **3. "No student add/edit/remove"** | **Confirmed Real Gap (`P0 #2`).** Resolved by adding full manual student CRUD (`StudentController`) for direct mid-year/transfer enrollments outside admissions, parent linking, roll number management, and soft-delete/archive (preserving fee/attendance history). |
| **4. "No student status updates"** | **Confirmed Real Gap (`P0 #2`).** Resolved by introducing a strict Student Status field (`Active`, `On Leave`, `Suspended`, `Transferred Out`, `Graduated`, `Expelled`, `Inactive/Dropped`) backed by an immutable `StudentStatusLog` capturing **who changed the status, exact timestamp, and mandatory reason text**. |

---

## 3. 👩‍🏫 Teacher Staff Portal — `Tenant/Teacher/Dashboard.vue`

**Audience:** Teaching Faculty, Department Heads, Class Teachers.  
**Purpose:** Daily class schedule tracking, interactive attendance marking, lecture note distribution, and assignment grading.

### Expanded Feature Breakdown & Implementation Details

1. **Daily Academic Timetable & Schedule (Timetable Tab)**
   - **Dynamic Roster:** Displays today's assigned periods (`todayClasses`), mapped to subjects (`Subject` model), target sections (`SchoolClass` + `Section`), and room locations.
2. **One-Click Interactive Attendance Grid (`/teacher/attendance`)**
   - **Live Marking:** Renders a student grid (`AttendanceGrid.vue`) for the selected class period. Teachers can quickly toggle student status (`Present` ✓, `Absent` ✕, `Late` ⏰, or `Excused` 🏥).
   - **Batch Submission:** Submits attendance records in bulk via `TeacherPortalController@storeAttendance`, preventing duplicate entries for the same date and class section.
3. **Lecture Notes & Course Material Repository (Materials Tab & Modal)**
   - **Multi-Format Uploads:** Teachers can attach lecture slides, PDF worksheets, video recordings (`file_url`), or reference docs bound to specific `subject_id` entries.
   - **Student Access Control:** Published materials instantly populate the `Materials Tab` inside the `Student Portal` for students enrolled in that specific class/section.
4. **Assignment & Homework Management (Assignments Tab)**
   - **Due Date & Grading Tracking:** Lists active assignments along with total submission counts and grading progress.

### 🚩 Missing / Broken Flow & Resolution Status
- **Gradebook & Exam Marks Entry:** While assignments and attendance are tracked, the portal lacks a dedicated **Exam Gradebook / Result Sheet** tab where teachers can input mid-term or final term marks (`Marks` / `ExamResult` models) to generate automated Report Cards (`Transcript.vue`).
- **Parent-Teacher Communication Channel:** Teachers cannot send direct feedback notes or behavioral observations (`StudentObservation` records) to individual parents from the student attendance grid.

---

## 4. 🎒 Student Academic Portal — `Tenant/Student/Dashboard.vue`

**Audience:** Enrolled Students (`user_type = 'student'`).  
**Purpose:** Courseware consumption, homework submission, attendance self-monitoring, and school notice awareness.

### Expanded Feature Breakdown & Implementation Details

1. **Personalized Academic Workspace (`Student/Dashboard.vue`)**
   - **Header Branding:** Dynamically displays the school's custom uploaded logo (`$page.props.tenant.logo`) alongside the student's current class and section assignment (`student.current_class.name`).
2. **Subject & Material Library (Materials Tab)**
   - **Filtered Feed:** Automatically filters all downloadable lecture notes, chapter outlines, and video links uploaded by teachers for the student's assigned `class_id` and `section_id`.
3. **Assigned Homework & Deadlines (Assignments Tab)**
   - **Status Indicators:** Clearly highlights pending vs. completed homework assignments with due timestamps and submission status badges.
4. **Attendance Record & Percentage Tracker (Attendance Tab)**
   - **Self-Service Verification:** Lets students view their monthly present/absent history and overall attendance percentage, encouraging accountability before exam eligibility cutoffs.

### 🚩 Missing / Broken Flow & Resolution Status
- **Assignment File Upload Workflow:** The `Assignments Tab` currently displays due dates and titles, but lacks a dropzone modal (`<input type="file">`) for students to upload their completed homework PDFs or ZIP files directly to the teacher for grading.
- **Online Quiz / MCQ Assessment Engine:** No built-in quiz player (`QuizAttempt` model) for timed online class tests or weekly objective assessments.

---

## 5. 👨‍👩‍👧‍👦 Parent & Family Workspace — `Tenant/Parent/Dashboard.vue`

**Audience:** Parents / Guardians (`user_type = 'parent'`).  
**Purpose:** Multi-child oversight, fee challan payments, attendance monitoring, and academic progress tracking.

### Expanded Feature Breakdown & Implementation Details

1. **Multi-Child Switcher & Consolidated Oversight (`parent.children`)**
   - **Unified Account:** A single parent login account can be linked to multiple siblings enrolled across different grade levels or sections within the same school.
   - **Instant Context Switching:** Parents can click between children (`ActiveChild` selector) to instantly reload attendance records, fee challans, and notices specific to that child.
2. **Fee Challan Review & Online Payment Gateway (`/parent/challans/{id}/pay`)**
   - **Challan Status Roster:** Lists all issued tuition fee challans with clear status breakdown (`Paid`, `Pending`, `Overdue`).
   - **Integrated Payment Integration (`JazzCash` / `Stripe`):** Connects parents directly to online checkout workflows (`Checkout.vue`), allowing instant digital fee settlement without visiting the school bank branch.
3. **Real-Time Child Attendance & Notice Feed**
   - **Daily Absence Alerts:** Parents can review daily attendance logs and see immediate records when a child is marked `Absent` or `Late`.

### 🚩 Missing / Broken Flow & Resolution Status
- **Parent Access Code / Sibling Linking UI:** Currently, students are linked to parents during initial admission, but there is no self-service `[Link Another Child]` modal where parents can input a `Family Access Code` provided by the school registrar.
- **Official Fee Receipt Download PDF:** After a fee challan status turns `Paid`, there is no button to generate and download a formal, printable **Fee Payment Receipt PDF** stamped with the uploaded School Logo.

---

## 🔍 Consolidated Cross-Platform Gap List (Highest Leverage Roadmap)

| # | Priority | Domain / Portal | Feature Gap / Architectural Need | Proposed Technical Resolution |
|---|---|---|---|---|
| 1 | **RESOLVED** | **Principal Admin & Login** | **School Logo & Custom Branding (`P2 #12`)** | Added `AdminPortalController@updateLogo` (`/admin/settings/logo`) + `removeLogo` endpoints. Shared `$page.props.tenant.logo` globally via `HandleInertiaRequests` and dynamically rendered in `Tenant/Auth/Login.vue` and all 4 portal sidebars. |
| 2 | **P0 (CRITICAL)** | **Principal Admin** | **User Management by Role (`P0 #1`)** | Build `StaffController` (`/admin/users`) with role selector (`Teacher`, `Admin`, `Registrar`, `Accountant`, `Front Desk`, `Librarian`), dynamic role fields, deactivation status toggles, admin password resets, and CSV bulk import via `Laravel-Excel`. |
| 3 | **P0 (CRITICAL)** | **Principal Admin** | **Student Management CRUD & Status Lifecycle (`P0 #2`)** | Implement `StudentController` (`/admin/students`) with manual enrollment, parent linking, soft-delete archiving, defined status transitions (`Active`, `On Leave`, `Suspended`, `Transferred Out`, `Graduated`, `Expelled`, `Inactive/Dropped`), immutable `StudentStatusLog` audit trail, section transfer, and bulk promotion tool. |
| 4 | **P0 (CRITICAL)** | **Principal Admin** | **Admissions Pipeline Rework (`P0 #3`)** | Refactor `AdmissionController` (`/admin/admissions`) with public web form API (`POST /api/v1/inquiries`), front-desk walk-in entry, interactive Kanban pipeline, waitlist queue, communication log (`AdmissionCommunication`), and automated first fee challan generation on enrollment. |
| 5 | **P1 (HIGH)** | **Principal Admin** | **Class, Section & Subject Roster (`P1 #4`)** | Expand `SchoolClass`, `Section` (with homeroom teacher and `max_capacity`), and `Subject` (`SubjectTeacher` pivot) management tables feeding timetable and gradebook. |
| 6 | **P1 (HIGH)** | **Principal Admin** | **Timetable Builder & Conflict Detection (`P1 #5`)** | Build interactive weekly grid (`TimetableSlot`) with automated conflict detection across teachers and physical rooms, plus publish/unpublish sync to Teacher/Student portals. |
| 7 | **P1 (HIGH)** | **Principal Admin** | **Fee Structure & Bulk Challan Generation (`P1 #6`)** | Create `FeeHead`, `FeeConcession`, and `PaymentReceipt` models. Implement `GenerateMonthlyChallansJob` for bulk challan creation and `SendDefaulterRemindersJob` for SMS/WhatsApp overdue follow-ups. |
| 8 | **P1 (HIGH)** | **Principal Admin & Staff** | **Staff Leave & Attendance Workflow (`P1 #7`)** | Add `StaffLeaveRequest` with Principal approval workflow, `SubstituteAssignment` for timetable coverage, and dedicated biometric/grid `StaffAttendance` tracking. |
| 9 | **P1 (HIGH)** | **Principal Admin** | **Notice Board Publishing Controls (`P1 #8`)** | Implement `NoticeController@store/update/destroy` with rich-text editor, multi-audience targeting (`All`, `Teachers`, `Students`, `Parents`, `Class`), future scheduled publishing, urgent SMS push, and `SchoolEvent` calendar. |
| 10 | **P1 (HIGH)** | **Principal Admin & Teacher** | **Exams, Gradebook & Report Cards (`P1 #9`)** | Build `ExamTerm`, `GradingScheme`, and `ExamResult` models. Create teacher gradebook entry grid and automated PDF report card generator (`ReportCardController@generate`) with promotion status flagging. |
| 11 | **P1 (HIGH)** | **Principal Admin** | **Executive Reports & Analytics (`P1 #10`)** | Build `/admin/reports` with enrollment growth trends, section attendance trends, fee collection vs. defaulter cash flow, admissions conversion analytics, and CSV/PDF export. |
| 12 | **P1 (HIGH)** | **Teacher & Student** | **Student Assignment Submission Uploads** | Create `AssignmentSubmission` model, controller route (`POST /student/assignments/{id}/submit`), and dropzone modal in `Student/Dashboard.vue`. |
| 13 | **P1 (HIGH)** | **Super Admin / Core** | **JazzCash Webhook Signature Security** | Implement cryptographic HMAC-SHA256 signature checking inside `JazzCashWebhookController` before marking `FeeChallan` records as `paid`. |
| 14 | **P2 (MEDIUM)** | **Principal Admin** | **Roles & Permissions Matrix (`P2 #11`)** | Integrate `Spatie/Laravel-Permission` for custom role definitions beyond defaults and build interactive permission matrix grid. |
| 15 | **P2 (MEDIUM)** | **Principal Admin** | **Academic Year / Session Management (`P2 #12`)** | Build `AcademicSession` management to open/archive school years (`2026-2027`), configure holiday calendars, and auto-fill document templates (SLC, Bonafide, ID Cards). |
| 16 | **P2 (MEDIUM)** | **Parent Portal** | **Printable Fee Receipt Generator** | Integrate `barryvdh/laravel-dompdf` or Inertia printable view for any `FeeChallan` where `status == 'paid'`, incorporating `$page.props.tenant.logo`. |
| 17 | **P2 (LOW)** | **Principal Admin** | **Library, Transport & Asset Modules (`P2 #13-15`)** | Phase in secondary ERP modules: book inventory/fine tracking (`/admin/library`), bus routes/driver assignment (`/admin/transport`), and asset inventory tracking (`/admin/inventory`). |
| 18 | **P2 (LOW)** | **Super Admin** | **Tenant Storage Quota Enforcement** | Add validation hook in `TeacherPortalController@storeMaterial` and `AdminPortalController@updateLogo` checking total tenant disk usage against subscription plan limits. |
| 19 | **P1 (HIGH)** | **Super Admin** | **Dunning & Subscription Lifecycle** | Implement automated payment retry logic, grace periods, and auto-suspension for failed payments/expired cards. |
| 20 | **P1 (HIGH)** | **Super Admin** | **Trials, Upgrades & Coupons** | Build trial-to-paid conversion flows, self-serve upgrade/downgrade UI for tenants, and coupon/referral code redemption logic. |
| 21 | **P1 (HIGH)** | **Super Admin** | **Tenant Impersonation** | Add secure "Login As Tenant" capability for Super Admins to debug tenant issues without requesting passwords. |
| 22 | **P1 (HIGH)** | **Super Admin** | **Custom Domains & White-Labeling** | Enable custom FQDN mapping (`school.com`) via `Stancl\Tenancy` domain models and allow reseller branding. |
| 23 | **P2 (MEDIUM)** | **Super Admin** | **Usage-Based Billing / Metering** | Introduce metering logic for pricing that scales dynamically with active student count or storage consumption. |
| 24 | **P2 (MEDIUM)** | **Super Admin** | **Central-Level Audit Logs** | Track and log all Super Admin actions (plan changes, suspensions, config updates) centrally for platform accountability. |
| 25 | **P2 (MEDIUM)** | **Super Admin** | **Data Export & Offboarding** | Develop an automated workflow for GDPR-compliant data deletion and structured export (SQL/CSV) upon tenant cancellation. |
| 26 | **P2 (MEDIUM)** | **Super Admin** | **API Rate Limiting Per Tenant** | Configure tenant-aware rate-limiting middleware to prevent noisy neighbor degradation on the shared platform. |
