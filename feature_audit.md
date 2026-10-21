# Detailed Audit of LMS Features

## 1. Central SaaS Management (Super Admin Portal)
- **Multi-Tenancy Architecture**: Dynamic provisioning of schools (tenants) on distinct subdomains or custom domains.
- **Super Admin Dashboard**: Centralized view of all tenants, subscriptions, and system health.
- **SaaS Subscription & Billing**: Checkout flows with Stripe integration, recurring plans management, and coupon/discount codes.
- **Tenant Lifecycle Management**: Toggle active/inactive states, offboarding, database backups, and synchronization.
- **Impersonation**: Super admins can seamlessly and securely log into any tenant's dashboard to provide support.
- **Manual Payment Approvals**: Workflow for approving or rejecting manual bank transfers/payments from schools.

## 2. School Administration (Principal & Admin Portal)
- **Admissions Management**: Handles public inquiries, student applications, merit evaluation, and final enrollment workflows.
- **User (Staff) Management**: Role-based access control, CRUD operations, status toggling, password resets, and bulk user imports.
- **Student Lifecycle Management**: Comprehensive student records, section transfers, bulk promotions across academic years, and document management.
- **Fee Management System**: Define fee structures by class, set student-specific overrides, generate recurring monthly challans (invoices), and handle defaulters (reminders/waivers).
- **Academic Structure**: Configure academic years, terms, classes, and sections (including presets for British O/A levels).
- **Timetable Builder**: Create class schedules with automatic conflict detection.
- **Exam & Gradebook**: Define exams, bulk-enter marks, and automatically generate comprehensive term report cards.
- **Attendance & Leave Management**: Staff check-in/out, leave request approvals for both staff and students.
- **Communication & Notices**: Publish school-wide circulars to the notice board and send targeted reminders.
- **School Branding**: Customize tenant-specific logos, branding colors, and payment gateways.
- **Sports & Extracurriculars**: Manage sports teams, activities, and student participations.
- **Disciplinary Penalties**: Record and manage student penalties and infractions.
- **Data Imports & Exports**: Robust CSV upload tools for onboarding users/classes/students, and comprehensive report exports.

## 3. Extended ERP Modules
- **HR & Payroll**: Manage staff salary structures, generate monthly payrolls, and process payslips.
- **Finance & Accounting**: Track vendors, record school expenses, and manage parent refund requests.
- **Library Management**: Catalog books, manage inventory, and track book issuance and returns by students/staff.
- **Transport Management**: Maintain fleets (vehicles), define routes, manage stops, and allocate transport to students.
- **Hostel Management**: Manage hostel buildings, rooms, and allocate/vacate room assignments for boarding students.
- **Inventory & Asset Management**: Track school assets, categorize items, and manage internal issuance requests.
- **Event Calendar**: Manage school-wide academic and extracurricular events.
- **Certificate Generator**: Design certificate templates, and issue/print certificates in bulk for students (e.g., leaving certificates, achievement awards).

## 4. Teacher Portal
- **Dashboard & Profile**: Teacher-specific overview and profile management.
- **Classroom Management**: Upload study materials and create assignments.
- **Grading & Evaluation**: Grade student submissions manually or utilize AI-assisted evaluation tools.
- **Attendance**: Record daily student attendance manually or via AI Snapshot processing.
- **Observations & Marks**: Record behavioral observations and enter subject marks.
- **Parent-Teacher Chat**: Direct messaging system for communicating with parents regarding student progress.
- **Leaves**: Submit personal leave requests to administration.

## 5. Student Portal
- **Dashboard & Profile**: Secure student login to view academic progress.
- **Assignments**: Submit homework/assignments digitally, appeal grades, and ask questions directly to teachers.
- **Study Materials**: Access and bookmark uploaded course materials.
- **Communication**: Read school notices and view report cards.
- **Requests**: Submit leave requests and join/leave sports activities.

## 6. Parent/Guardian Portal
- **Dashboard & Monitoring**: View multiple children's academic progress from a single dashboard.
- **Fee Payments**: View generated fee challans and process payments directly via Stripe or local gateways (e.g., JazzCash IPN).
- **Leave Requests**: Submit official leave requests on behalf of their children.
- **Parent-Teacher Chat**: Communicate directly with class teachers.
