# BookTala production readiness report

**Audit date:** 24 November 2026

**Deployment target:** Self-hosted Coolify on Contabo, PostgreSQL 16

**Status:** Ready for staging after production credentials, DNS, backups, and external services are configured

## Verified state

- The complete SQLite suite passes: 338 tests and 1,848 assertions.
- The complete PostgreSQL 16 suite passes: 335 tests and 1,836 assertions. The two external-service reliability tests and the later housekeeping isolation regression also pass against PostgreSQL.
- The production Docker image builds with PHP 8.3, Apache, the PostgreSQL driver, Supervisor, the queue worker, and the Laravel scheduler.
- The built container returns HTTP 200 from `/up` and `/` while connected to PostgreSQL 16.
- Laravel Pint, Composer validation, Composer security audit, npm security audit, and the Vite production build pass.

## Changes completed during this audit

### PostgreSQL compatibility

- Replaced database-specific aggregate assumptions and aliases with PostgreSQL-compatible handling.
- Added migrations that remove the legacy staff department constraint and expand operational status constraints.
- Verified every migration and the application test suite on a real PostgreSQL 16 instance.

### Security and tenant isolation

- Restricted public media delivery to an explicit allowlist.
- Moved guest identification, payment receipts, and housekeeping evidence to private storage.
- Separated public and private R2 configuration so private records cannot share a public bucket.
- Enforced property and owner scoping for expenses, owner statements, and related finance access.
- Added production checks for HTTPS, secure encrypted sessions, trusted proxies, production mail, queue/cache/session drivers, Paystack, PostgreSQL, and storage separation.

### Runtime reliability

- Added bounded connect and request timeouts for Paystack and Termii.
- Added retry handling only for safe Paystack verification requests; payment-creation POST requests are not blindly retried.
- Corrected Termii sender configuration and covered it with tests.
- Runs Apache, a persistent Laravel queue worker, and the scheduler under Supervisor in the container.
- Uses `onOneServer()` for scheduled work to prevent duplicate execution when the application is scaled.

### Deployment

- Added a multi-stage production Dockerfile and Coolify-ready environment template.
- Added a PostgreSQL-backed CI workflow.
- Removed obsolete cPanel/MySQL deployment scripts that could lead to an unsafe deployment path.
- Added the deployment and recovery runbook at [`docs/deployment/coolify-postgresql.md`](deployment/coolify-postgresql.md).

## Required before production traffic

1. Create the PostgreSQL service on Coolify's private network and use a dedicated application role and database.
2. Configure all variables from `.env.production.example`; generate a unique `APP_KEY` and never reuse the test key.
3. Configure live SMTP, Paystack keys and webhook, Termii credentials, and separate public/private R2 buckets.
4. Point DNS to Coolify, enable TLS, set the final `APP_URL`, and run `php artisan app:production-check` inside the deployed container.
5. Attach persistent storage for `storage/app` when local storage is used.
6. Schedule encrypted PostgreSQL backups outside the Contabo server and perform a restore drill before launch.
7. Run `php artisan migrate --force`, then verify `/up`, booking, payment verification, email, SMS, queues, scheduled jobs, and owner/property finance isolation in staging.
8. Configure uptime and error monitoring and alert on failed jobs, queue depth, disk usage, database capacity, and backup failures.

## Release decision

The code and container are suitable for a staging deployment on the target PostgreSQL/Coolify architecture. Production approval remains conditional on successful staging acceptance, valid live credentials, verified Paystack webhooks, TLS, off-server backups with a tested restore, and operational monitoring.
