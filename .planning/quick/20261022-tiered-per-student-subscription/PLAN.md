---
task: tiered-per-student-subscription
created_at: 2026-10-22T23:51:00+05:00
status: in-progress
type: quick
---

# Plan: Tiered Per-Student Subscription System (Starter, Professional, Premium, Enterprise)

## Context & Objectives
The platform currently utilizes fixed flat-fee subscription pricing (`price_monthly`) with hard student ceilings. The user requested switching to a usage-based, per-student monthly billing model with a guaranteed minimum bill floor across 4 distinct packages:
1. **Starter (Small schools):** PKR 20 / student / month (Min bill: PKR 2,000/month). Basic SIS, attendance, timetables, exams, homework, basic portals.
2. **Professional (Recommended):** PKR 35 / student / month (Min bill: PKR 3,500/month). Everything in Starter + Fee management & payments, parent-teacher communication, assignments, reports & analytics, role audit history, priority support.
3. **Premium (Advanced):** PKR 55 / student / month (Min bill: PKR 5,500/month). Everything in Professional + Advanced analytics, configurable workflows, advanced assessments, data import assistance, enhanced support.
4. **Enterprise (Custom):** Central multi-campus management, consolidated reporting, custom integrations, negotiated volume pricing. Direct sales modal.

## Tasks

### Task 1: Database Migration & Model Methods
- Create migration `add_per_student_pricing_to_subscription_plans_table`:
  - `pricing_type`: string, default 'per_student'
  - `price_per_student`: decimal(10, 2), default 20.00
  - `minimum_monthly_bill`: decimal(10, 2), default 2000.00
  - `tagline`: string nullable
  - `modules`: json nullable
- Update `app/Models/SubscriptionPlan.php`:
  - Add `$fillable`, `$casts`.
  - Add `calculateMonthlyBill(int $studentCount): float`.
  - Add `hasModule(string $module): bool`.

### Task 2: Sync Subscription Plans Seeder/Data
- Create migration or command to update existing plans in `subscription_plans` table to the 4 defined packages:
  - `starter`: PKR 20/student, Min PKR 2,000.
  - `professional`: PKR 35/student, Min PKR 3,500.
  - `premium`: PKR 55/student, Min PKR 5,500.
  - `enterprise`: Custom pricing, negotiated volume pricing.

### Task 3: Feature Gating & Module Access Control
- Update `EnforceTenantQuotas.php` to check module access permissions:
  - If tenant is on Starter plan and attempts to access Fee management routes (`admin/fees/*`), abort with 403 or redirect with upgrade prompt.
- Update `HandleInertiaRequests.php` / tenant layout to pass accessible modules to frontend navigation.

### Task 4: Landing Page Pricing Redesign (`Landing.vue`)
- Update `Landing.vue`:
  - Show the 4 tiers: Starter, Professional (highlighted as Recommended), Premium, Enterprise.
  - Display PKR / student / month prominently with minimum bill note.
  - Exact feature lists matching user requirements.
  - Enterprise card triggers "Talk to an Expert" consultation modal.

### Task 5: Checkout Page Updates (`Checkout.vue` & `SaaSCheckoutController.php`)
- Update `SaaSCheckoutController.php` to support the new plans and per-student calculation.
- Update `Checkout.vue`:
  - Add interactive student count estimator slider/input showing real-time monthly bill calculation with minimum bill threshold.
  - Initial payment charges the minimum monthly bill as month 1 base deposit, or provides 14-day free trial.

### Task 6: Feature Tests & Verification
- Create `tests/Feature/TieredPerStudentSubscriptionTest.php` testing:
  - Plan calculations (`calculateMonthlyBill`).
  - Minimum bill enforcement.
  - Feature gating for Starter vs Professional tenants.
  - Landing and checkout page responses.

### Task 7: Build, Push to GitHub & Deploy to VPS
- Run all tests.
- Compile assets (`npm run build`).
- Push commits to GitHub `origin/master`.
- Deploy to VPS `194.163.142.50`, run migrations, and verify live on `https://raibix.com`.
