---
task: dynamic-subscription-plans
created_at: 2026-10-23T11:55:00+05:00
status: in-progress
type: quick
---

# Plan: Dynamic Subscription Plans for Landing Page & Dashboard Alignment

## Context
1. The landing page (`Central/Landing.vue`) currently hardcodes plans fallback (`if (props.plans && props.plans.length >= 4) return props.plans; return defaultPlans;`) with hardcoded template logic for specific plan codes and per-student fields only.
2. The SuperAdmin Dashboard (`Central/Dashboard.vue` & `SuperAdminController.php`) was fetching all plans including 2 legacy inactive plans (`Essential Plan` and `Signature Plan` with `is_active: false`), displaying flat monthly prices without active/inactive indicators, and lacking fields for `pricing_type`, `price_per_student`, `minimum_monthly_bill`, `tagline`, `summary`, and `is_active`.
3. This caused the dashboard to show packages different from the landing page, and edits in dashboard did not properly update landing page pricing.

## Objectives
1. Make `Landing.vue` truly dynamic: use `props.plans` without requiring `>= 4` plans, support `per_student`, `flat`, and `custom` pricing models, dynamic grid sizing, and dynamic badges/CTAs.
2. Upgrade `SuperAdminController.php` with support for `pricing_type`, `price_per_student`, `minimum_monthly_bill`, `tagline`, `summary`, `is_active` in `storePlan` and `updatePlan`, plus `togglePlanStatus`.
3. Upgrade `Central/Dashboard.vue`: show Active/Inactive status badges, dynamic pricing summaries, 1-click publish/unpublish toggle, and full plan creation/editing modal fields.
4. Verify tests pass and Vite builds cleanly.
