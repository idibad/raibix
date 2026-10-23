---
task: dynamic-subscription-plans
completed_at: 2026-10-23T12:09:00+05:00
status: complete
type: quick
---

# Summary: Dynamic Subscription Plans & Dashboard-Landing Alignment

## Root Causes Identified
1. **Landing Page Hardcoding**:
   - `resources/js/Pages/Central/Landing.vue` had `if (props.plans && props.plans.length >= 4)` which strictly required $\ge 4$ plans before it would use database plans, falling back to a static `defaultPlans` array otherwise.
   - The UI template hardcoded assumptions for specific plans (`plan.code === 'professional'`, `plan.code === 'enterprise'`), per-student text labels, and fixed 4-column layout (`xl:grid-cols-4`).
2. **Dashboard Discrepancy**:
   - `SuperAdminController::index()` queried `SubscriptionPlan::all()`, returning both active and inactive legacy packages (`Essential Plan` and `Signature Plan` from early schema seed).
   - In contrast, `SuperAdminController::landing()` queried `where('is_active', true)`, returning only the active tiered plans (`starter`, `professional`, `premium`, `enterprise`).
   - The Super Admin Dashboard had no indicators for Active/Inactive plans and no toggle button to publish/unpublish plans to the landing page.
   - The Super Admin Plan Modal only allowed modifying `price_monthly`, missing fields for `pricing_type`, `price_per_student`, `minimum_monthly_bill`, `tagline`, `summary`, and `is_active`.

## Solutions Implemented
1. **Dynamic Landing Page (`Landing.vue`)**:
   - Removed the $\ge 4$ restriction; uses `props.plans` dynamically whenever non-empty.
   - Built dynamic grid sizing (`plansGridClass`) that adapts neatly to 1, 2, 3, 4, or 5+ plans.
   - Built polymorphic price display supporting `per_student`, `flat` monthly/yearly, and `custom` volume pricing.
   - Dynamic badges and highlights based on `plan.tagline`, `plan.featured`, or plan identifiers.
   - Dynamic CTA buttons (linking to `/checkout/{plan.code}` or opening the Consultation Modal for custom plans).
2. **Dashboard Management & Active Status Alignment (`Dashboard.vue` & `SuperAdminController.php`)**:
   - Added `togglePlanStatus` endpoint (`POST /plans/{plan}/toggle-status`) with 1-click "Publish" / "Unpublish" button on each card.
   - Added clear status tags on every card: `Published (Landing Page: Visible)` vs `Hidden (Landing Page: Inactive)`.
   - Updated the Plan Modal with full fields: `pricing_type` dropdown, `price_per_student`, `minimum_monthly_bill`, `tagline`, `summary`, and `is_active` checkbox.
   - Handled `flat` pricing in `SubscriptionPlan::calculateMonthlyBill` and `Checkout.vue`.
3. **Verification**:
   - Added 4 new feature test cases in `TieredPerStudentSubscriptionTest.php`.
   - All 92 feature & unit tests passing (322 assertions).
   - Production Vite bundle built cleanly.
