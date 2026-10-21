---
last_mapped_commit: 2edfa5c410d4014d0d8a964ec213a6bdef8194a6
last_mapped_at: 2026-10-22
---
# Technology Stack

**Analysis Date:** 2026-10-22

## Languages

**Primary:**
- PHP 8.2+ (`^8.2` defined in `composer.json`) - Core backend application, tenancy middleware, REST/Inertia controllers, services, database models, migrations.
- JavaScript / ECMAScript (ES2022+ / Vue 3 Composition API) - Client-side single-page UI built via Inertia.js Vue 3.

**Secondary:**
- SQL (SQLite for local dev, MySQL / PostgreSQL schema support via Stancl Tenancy managers).
- CSS / Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`, `tailwindcss: ^4.0.0`) for utility-first styling.

## Runtime

**Environment:**
- PHP CLI & Web SAPI 8.2+
- Node.js 20.x+ (used for Vite bundle compilation and frontend tooling)

**Package Managers:**
- Composer (`composer.json`, lockfile `composer.lock` present)
- npm (`package.json`, lockfile `package-lock.json` present)

## Frameworks

**Core:**
- Laravel Framework 12.0 (`laravel/framework: ^12.0`) - Full-stack PHP application framework
- Inertia.js Laravel adapter (`inertiajs/inertia-laravel: ^3.1`) & `@inertiajs/vue3: ^3.6.1` - Monolithic single-page application bridge
- Vue 3 (`vue: ^3.5.39`) - Reactive frontend component layer

**Multi-Tenancy:**
- Stancl Tenancy v3 (`stancl/tenancy: ^3.10`) - Multi-database and single-database multi-tenancy engine with subdomain/domain resolution and tenant lifecycle hooks

**Authorization & Permissions:**
- Spatie Laravel Permission (`spatie/laravel-permission: ^6.25`) - Role-based access control (RBAC) package

**Testing:**
- PHPUnit 11.5 (`phpunit/phpunit: ^11.5.50`) - Automated testing suite
- Mockery 1.6 (`mockery/mockery: ^1.6`) - Mocking library for unit/feature tests

**Build/Dev Tools:**
- Vite 7.0 (`vite: ^7.0.7`) - Next-generation frontend build tool and hot module replacement dev server
- `@vitejs/plugin-vue: ^6.0.7` - Vue Single File Component support
- `laravel-vite-plugin: ^2.0.0` - Integration between Laravel and Vite asset bundling
- Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`)
- Lucide Vue Next (`lucide-vue-next: ^1.0.0`) - Icon system
- Concurrently (`concurrently: ^9.0.1`) - Parallel execution of artisan serve, queue listener, log tailing, and Vite dev server

## Key Dependencies

**Critical:**
- `stancl/tenancy` (v3.10) - Isolates database connections, cache tags, filesystem disks, and queues per school tenant (`app/Models/Tenant.php`, `config/tenancy.php`).
- `inertiajs/inertia-laravel` (v3.1) - Renders Vue SPA pages directly from Laravel controllers without boilerplate REST serializing (`app/Http/Middleware/HandleInertiaRequests.php`).
- `spatie/laravel-permission` (v6.25) - Manages roles and permissions for school staff, teachers, students, and parents.
- `stripe/stripe-php` (v21.0) - SaaS subscription checkout, payment processing, and webhooks (`app/Http/Controllers/Central/SaaSCheckoutController.php`).
- `tightenco/ziggy` (v2.6) - Exposes named Laravel routes to JavaScript/Vue client components.

**Infrastructure:**
- `laravel/pail` (v1.2.2) - Real-time terminal log streaming for local debugging.
- `laravel/sail` (v1.41) - Docker environment integration for Laravel development.
- `laravel/pint` (v1.24) - Opinionated PHP code style fixer.

## Configuration

**Environment:**
- Configured via `.env` files.
- Single `.env` file currently exists with hardcoded development values (`APP_ENV=local`, `APP_DEBUG=true`, SQLite connection, database queue and session).
- Environment separation (local, staging, production) is currently missing and required.

**Build Configuration:**
- `vite.config.js` - Configures Vite plugins (`laravel-vite-plugin`, `@vitejs/plugin-vue`, `@tailwindcss/vite`).
- `phpunit.xml` - PHPUnit environment test runner configuration.
- `composer.json` - Autoloading, scripts (`dev`, `test`, `setup`), minimum stability.

## Platform Requirements

**Development:**
- PHP 8.2+ with extensions: `pdo_sqlite`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`.
- Node.js 18+ and npm.
- Local SQLite database engine or MySQL 8.0+.

**Production Target:**
- Production Web Server (Nginx / Caddy / Apache) with PHP 8.2+ FPM.
- Production Database: MySQL 8.0+ or PostgreSQL 15+ (requires `CREATE DATABASE` privilege for tenant provisioning in multi-database mode).
- In-memory store: Redis 7.0+ for production cache, session, and queues.
- S3-compatible object storage (AWS S3, Cloudflare R2, or MinIO) for tenant isolated file storage.

---

*Stack analysis: 2026-10-22*
*Update after major dependency changes*
