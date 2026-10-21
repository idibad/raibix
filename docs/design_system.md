# Raibix Design System

> Quality standard: Linear, Stripe, Vercel, Framer. Premium, confident, quiet, and alive.

---

## 1. The Central Idea: The Intelligence Core
The purple-blue gradient dot from the Raibix logo is the **intelligence core**. The whole system is architected around it.
It serves as:
- The logo mark
- The hero interactive dome orb
- The active step indicator
- The selected plan beacon
- The workflow connector
- The core visual anchor in the center of the architecture diagram

### Dot Rules (Strict & Non-Negotiable)
- **Geometry**: Always a perfect circle (`aspect-ratio: 1/1; border-radius: 9999px;`).
- **Gradient**: Exactly 135deg `#8B5CF6` (violet) &rarr; `#2F6BFF` (Raibix blue).
- **Depth on Large Instances**: Radial highlight at 35%/25% (`#B79CFF`), soft inner glow (`inset 0 1px 2px rgba(255,255,255,0.5)`), subtle outer bloom (`rgba(139, 92, 246, 0.4)`), faint film grain.
- **Flat on Small Instances**: Small dots (&le; 16px) remain completely flat.
- **Prohibitions**: Never stretched, never outlined, never recoloured, never blurred into mush, and never used as bullet list decorations.

---

## 2. Design Tokens

### Color Palette (CSS Variables)
| Token | Light Mode | Dark Mode (`prefers-color-scheme` / `.dark`) | Usage |
|---|---|---|---|
| `--color-page` | `#FFFFFF` | `#0B0D14` | Primary page canvas |
| `--color-ink` | `#0B0D14` | `#F4F5F8` | Display, titles, prominent text |
| `--color-slate` | `#5B6270` | `#9AA1B0` | Body copy, secondary labels |
| `--color-mist` | `#F6F7F9` | `#12151E` | Alternate sections, input backdrops |
| `--color-line` | `#E6E8EE` | `#232838` | Hairline borders (1px) |
| `--color-blue` | `#2F6BFF` | `#2F6BFF` | Actions, active states, focus rings |
| `--color-blue-hover` | `#1E54DC` | `#457BFF` | Button hover states |
| `--color-tint` | `#EEF3FF` | `rgba(47, 107, 255, 0.12)` | Informational panels & instruction boxes |
| `--color-success` | `#15803D` | `#15803D` | Verified & positive indicators |
| `--color-error` | `#B91C1C` | `#B91C1C` | Validation errors & alerts |

> **Sanctioned Gradient Exceptions**: The gradient (`#8B5CF6` &rarr; `#2F6BFF`) is reserved strictly for the Intelligence Core dot/orbs and the "Not sure which suits your school?" expert callout box.

### Typography (Manrope / Inter Fallback)
| Role | Size | Line Height | Weight | Tracking | Color |
|---|---|---|---|---|---|
| **Display** | 64&ndash;80px | 1.04 | 800 | -0.03em | `--color-ink` |
| **H1** | 40&ndash;48px | 1.10 | 800 | -0.025em | `--color-ink` |
| **H2** | 28&ndash;32px | 1.20 | 700 | -0.02em | `--color-ink` |
| **H3** | 20px | 1.30 | 700 | -0.01em | `--color-ink` |
| **Body** | 16&ndash;18px | 1.60 | 400 | normal | `--color-slate` |
| **Label** | 13px | 1.40 | 600 | normal | `--color-ink` |
| **Eyebrow** | 13px | 1.00 | 700 | +0.08em (uppercase) | `--color-blue` |

### Spacing & Layout
- **Scale**: Strict 8px increment grid (8, 16, 24, 32, 40, 48, 64, 80, 112).
- **Sections**: 112px desktop (`py-28`), 72px mobile (`py-16`).
- **Content Max Width**: 1120px (`max-w-[1120px]`).
- **Text Measure**: Body text constrained to &le; 560px for effortless readability.

### Shape & Radii
- **Controls & Buttons**: 8px (`rounded-lg`) to full pills (`rounded-full`).
- **Panels & Cards**: 16px (`rounded-2xl`).
- **Containers**: 24px (`rounded-[24px]`).
- **Borders**: 1px line colour (`border border-[var(--color-line)]`).
- **Shadows**: Soft, long, low opacity only on hero preview, pricing container, and hover-lift states.

---

## 3. Motion System
- **Timing Curve**: `cubic-bezier(0.22, 1, 0.36, 1)`.
- **Durations**:
  - Micro-interactions: 200ms
  - Reveal transitions: 600&ndash;800ms
- **Hero Dome Orb**: Scroll-linked parallax (translate + slight scale), cursor pointer parallax (lerp physics, &plusmn;18px), and idle breathing loop (`scale 1 &rarr; 1.02` over 8s).
- **Floating Cards**: Independent float loops with staggered durations (6.5s to 9s), delays, and subtle rotation (&plusmn;1.5deg).
- **Buttons**: 2px lift on hover, scale 0.98 on press.
- **Marquee**: Seamless infinite loop (28s), pauses on hover.
- **Accordion**: Animated height with `+` rotating 45deg smoothly to `&times;`.
- **Reduced Motion**: Strictly respects `@media (prefers-reduced-motion: reduce)` by disabling parallax, floats, breathing, and marquee loops.

---

## 4. Key Components

### 1. Sticky Top Navbar
- Transparent backdrop blur (`backdrop-blur-md bg-[var(--color-page)]/80`).
- Hairline bottom border (`border-b border-[var(--color-line)]`).
- Logo with wordmark and inline Intelligence Core dot.
- Dark/Light mode switcher and "Get Started" pill.

### 2. Sticky Floating Bottom Pill Nav
- Glass pill centered at bottom (`bottom-6 left-1/2 -translate-x-1/2`).
- 5 navigation anchors: Overview, Story, Roles, The Core, Pricing.
- IntersectionObserver highlights active section and shows dot anchor.
- Automatically fades out when scrolling into the footer area.

### 3. Role Cards (4 Stakeholders)
- 4 tall cards: Admin, Teacher, Parent, Student.
- Role-specific interactive UI vignettes (multi-campus selector, biometric attendance, 1-Bill clearance voucher, lecture timeline).
- 4px lift, blue border highlight, and vignette animation on hover.

### 4. Overlapping Subdomain Showcases ("It all feels yours")
- Desktop browser mockup displaying `beaconhouse.raibix.cloud` with school branding.
- Phone mockup overlapping bottom-right with parent portal view.
- Real-time subdomain availability checker.

### 5. Pricing Container with Inset Depth
- Single rounded container (`rounded-[24px]` in `var(--color-mist)` with inner depth shadow).
- Three cards: Starter (PKR 20,000/mo), Essential (PKR 40,000/mo), Signature (PKR 50,000/mo).
- Essential active by default.
- Active card lifts, turns white, gets blue border and soft glow, siblings dim.
- Full-width organic wave gradient box below: "Not sure which suits your school?" + "Call an expert".

### 6. Checkout & Onboarding
- Compact header with "Back to Pricing".
- 3-step stepper: Plan &bull; School &amp; Admin &bull; Payment.
- Sticky order summary on left with plan switcher and 20% annual discount.
- Clean hairline-separated form sections (School, Administrator, Payment).
- Light-blue instruction panel with real Meezan Bank details and 1-click copy buttons.
- Secure file upload with 4MB validation, preview, and removal.
- Zero pre-filled or default passwords.
