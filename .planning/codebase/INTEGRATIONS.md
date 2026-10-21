---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# External Integrations

**Analysis Date:** 2026-10-22

## Payment Gateways

### Stripe (Central SaaS Billing)

- **Purpose:** SaaS recurring subscriptions and one-time registration payments for school tenants.
- **Client Library:** `stripe/stripe-php` (`^21.0`).
- **Implementation:** `app/Http/Controllers/Central/SaaSCheckoutController.php`.
- **Endpoints:**
  - `POST /checkout` - Creates Stripe Checkout Session or charges directly.
  - `POST /stripe/webhook` - Handles asynchronous payment events (`checkout.session.completed`, `invoice.payment_succeeded`, `customer.subscription.deleted`).
- **Configuration Keys:**
  - `STRIPE_KEY` - Publishable key.
  - `STRIPE_SECRET` - Secret API key.
  - `STRIPE_WEBHOOK_SECRET` - HMAC signing secret for webhook verification.

### JazzCash (Tenant School Fee Collection)

- **Purpose:** Mobile wallet and voucher payments for Pakistani school fee challans.
- **Service Class:** `app/Services/JazzCashService.php`.
- **Implementation:** `app/Http/Controllers/Tenant/ParentPortalController.php`, `PaymentController.php`.
- **Endpoints:**
  - `POST /parent/challans/{id}/pay` - Initiates payment or records simulated local transaction.
  - `POST /parent/payment/callback` - Unauthenticated Instant Payment Notification (IPN) callback from JazzCash gateway.
- **Security & Integrity:**
  - Computes and verifies SHA-256 HMAC `pp_SecureHash`.
  - In local development mode, simulated response code `'000'` is supported; needs strict production verification guard.
- **Configuration Keys:**
  - `JAZZCASH_MERCHANT_ID`, `JAZZCASH_PASSWORD`, `JAZZCASH_INTEGRITY_SALT`, `JAZZCASH_RETURN_URL`.

## Messaging & Notification Providers

### SMS Gateways

- **Service Class:** `app/Services/NotificationService.php`.
- **Supported Providers:**
  - **Twilio:** BasicAuth REST API (`https://api.twilio.com/2010-04-01/Accounts/.../Messages.json`).
  - **UrduBit / PariSMS:** Regional Pakistani bulk SMS gateway HTTP GET API (`https://api.urdubit.com/sms/send`).
- **Use Cases:** Student absence alerts (`sendAbsenceAlert`), fee defaulter overdue reminders (`sendDefaulterReminder`).
- **Current Issue:** HTTP calls execute synchronously inside the controller request thread instead of being deferred to background queue workers. Logging records plain phone numbers and message bodies (PII exposure).

### WhatsApp Cloud API (Meta)

- **Service Class:** `app/Services/NotificationService.php`.
- **Endpoint:** `https://graph.facebook.com/v19.0/{phone_id}/messages`.
- **Authentication:** Bearer token (`services.whatsapp.token`).
- **Current Issue:** Synchronous HTTP dispatch; raw message payload containing student names and fee amounts logged directly in application log files.

## AI & Computer Vision Services

### AI Vision & Automated Evaluation

- **Service Class:** `app/Services/AiVisionService.php`.
- **Functions:**
  - AI Face Recognition for automated attendance snapshots (`processAttendanceSnapshot`).
  - Automated student assignment grading / NLP evaluation (`evaluateAssignmentSubmission`).
- **External Dependencies:** OpenAI / Cloud Vision API endpoints via HTTP client.
- **Current Issue:** Synchronous execution causes HTTP timeouts when processing high-resolution images.

## Multi-Tenancy Database & Runtime

### Stancl Tenancy Bootstrappers

- **Configuration:** `config/tenancy.php`.
- **Database Bootstrapper:** `Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper` dynamically switches default database connection between Central and Tenant databases.
- **Cache Bootstrapper:** `Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper` applies tenant tag prefixes (`tenant_{id}`) to cache stores.
- **Queue Bootstrapper:** `Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper` injects tenant context into job payloads to ensure worker executes queries against the tenant's database.
- **Filesystem Bootstrapper:** `Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper` prefixes storage roots (`%storage_path%/app/tenant{id}`).

## Storage Providers

### Local Filesystem vs Cloud Object Storage

- **Configuration:** `config/filesystems.php`.
- **Current State:** Disks configured for `local`, `public`, and `s3`.
- **Risk Area:** Controllers currently bypass storage abstraction using raw `move(public_path('uploads/...'))`, leaving student documents, payment receipts, and logos outside the tenant-isolated storage partition and publicly accessible without authorization.

---

*Integrations analysis: 2026-10-22*
*Update after major dependency changes*
