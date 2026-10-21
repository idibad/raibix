# Raibix Production Verification Checklist

## 1. Pricing Mathematics & Calculations
- [x] **Starter Plan**:
  - Monthly: PKR 20,000 / month
  - Annual: PKR 192,000 / year (PKR 16,000/mo effective, saving 20% or PKR 48,000/yr)
  - Student Capacity: 300 students
- [x] **Essential Plan** (Default Active Tier):
  - Monthly: PKR 40,000 / month
  - Annual: PKR 384,000 / year (PKR 32,000/mo effective, saving 20% or PKR 96,000/yr)
  - Student Capacity: 1,000 students
- [x] **Signature Plan**:
  - Monthly: PKR 50,000 / month
  - Annual: PKR 480,000 / year (PKR 40,000/mo effective, saving 20% or PKR 120,000/yr)
  - Student Capacity: 5,000 students
- [x] **Taxes & Setup**:
  - Setup & TLS: Included in all packages
  - Provincial / Federal Tax: Set to "Exempt" per platform `tax_exemption_enabled = true`
  - Total Due Calculation: Reflects exact sum matching primary button label `Complete Order — PKR [Total]`
- [x] **Database Alignment**:
  - Verified against database `subscription_plans` records.
  - Plan code aliases (`growth` &rarr; `essential`, `enterprise` &rarr; `signature`) supported for full backward-compatibility.

## 2. Field Validation & Error Handling
- [x] **Campus Name**: Required, length &le; 255 characters.
- [x] **Subdomain Slug (`id`)**:
  - Alpha-dash only, sanitized on input (`[^a-z0-9-]`).
  - Real-time debounced asynchronous availability check via `/api/check-subdomain`.
  - Disables checkout button when `subdomainStatus === 'taken'`.
  - Inline feedback: "Checking...", "Available" (green/blue), or "Already taken" (error red).
- [x] **Administrator Account**:
  - Full Name: Required.
  - Email: Validated RFC email format.
  - Phone: Validated contact string.
- [x] **Form Submission**:
  - Duplicate submit prevention via Inertia `form.processing` disabled state.
  - Global error banner rendered with clear field-level error messages.

## 3. Password Security & Credential Handling
- [x] **Zero Default Passwords**:
  - Removed all hardcoded credentials (such as legacy `Secret@123`).
  - Password field initializes strictly empty (`''`).
  - No default password injected in session or database fallbacks.
- [x] **Input Masking & Controls**:
  - Password input masked (`type="password"`) by default.
  - Accessible Show / Hide toggle button.
  - Password hint: "Minimum 6 characters".
  - Enforced `autocomplete="new-password"` attribute.

## 4. File Upload Limits & MIME Validation
- [x] **Receipt Screenshot Upload**:
  - Server-side rule: `image|max:4096` (enforcing image format and 4MB maximum).
  - Client-side validation: Immediate rejection with warning banner if file &gt; 4MB.
  - Supported formats: PNG, JPEG, WEBP.
  - UX Controls: Filename and size formatted in MB, with one-click "Remove" and "Replace" actions.

## 5. Bank Transfer & Payment rails
- [x] **Bank Details**:
  - Bank: Meezan Bank Limited
  - Account Title: Raibix Technologies
  - Account Number: `1201-0982-12001`
  - IBAN: `PK00MEZN01201098212001`
  - Exact Amount: Matches current price based on billing frequency.
- [x] **Copy Buttons**:
  - Independent 1-click clipboard copy triggers for Account Number, IBAN, and Amount.
  - 2-second visual confirmation ("Copied").
- [x] **Instruction Clarity**:
  - Preserves exact required copy: "Transfer the amount shown above and upload your payment receipt. Your subscription will be activated after payment verification."

## 6. Mobile & Responsive Layout
- [x] **Viewport Adaptation (360px &rarr; 1440px+)**:
  - Top navigation collapses and provides essential action triggers.
  - Floating bottom pill nav compresses smoothly.
  - Mobile checkout displays order summary above the form.
  - Sticky bottom checkout bar on mobile viewports for effortless single-thumb submission.
  - Hero dome orb and floating cards scale responsively without causing horizontal overflow (`overflow-x: hidden`).

## 7. Motion & Accessibility (a11y)
- [x] **Reduced Motion**:
  - `@media (prefers-reduced-motion: reduce)` disables orb parallax, floating loops, breathing animations, and infinite marquee.
  - Replaces motion with instant opacity transitions.
- [x] **Contrast & Focus**:
  - WCAG AA contrast ratio achieved across light (`#0B0D14` on `#FFFFFF`) and dark (`#F4F5F8` on `#0B0D14`) modes.
  - High-visibility 2px focus ring (`outline: 2px solid var(--color-blue)`) on all interactive controls.
- [x] **Keyboard Navigation & ARIA**:
  - Full keyboard accessibility for tabs, toggles, form fields, and copy triggers.
  - `aria-expanded` attributes on FAQ accordion items with rotating `+` to `&times;` icon.
  - Semantic HTML landmarks: `<header>`, `<nav>`, `<main>`, `<section>`, `<aside>`, `<footer>`.
