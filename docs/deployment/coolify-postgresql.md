# Coolify deployment on Contabo

BookTala runs as one container containing Apache, the Laravel queue worker, and the Laravel scheduler. PostgreSQL runs as a separate Coolify database resource on the same private network.

## Create the resources

1. Create a PostgreSQL resource in Coolify. Enable persistent storage and automated backups.
2. Create an application from this repository and select the repository `Dockerfile` build pack.
3. Expose container port `80` and attach the production domain with HTTPS.
4. Add a persistent volume mounted at `/var/www/html/storage` when `FILESYSTEM_DISK=local`. R2 users still benefit from persistent logs, but uploads live in R2.
5. Set the health check path to `/up`.

Use the PostgreSQL resource's private hostname, not a public IP. Start from `.env.production.example` and set these values in Coolify's environment panel:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://booktala.example
APP_KEY=base64:generated-value
TRUSTED_PROXIES=*

DB_CONNECTION=pgsql
DB_HOST=postgres-private-hostname
DB_PORT=5432
DB_DATABASE=booktala
DB_USERNAME=booktala
DB_PASSWORD=strong-generated-password
DB_SSLMODE=prefer

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

## Cloudflare Turnstile

Configure the production widget credentials as Coolify environment variables. Never commit the secret to the repository.

```dotenv
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=<Cloudflare widget site key>
TURNSTILE_SECRET=<Cloudflare widget secret>
TURNSTILE_HOSTNAMES=booktala.com
TURNSTILE_TIMEOUT=10
```

The Cloudflare widget must include the same production hostname. Keep `localhost` and `127.0.0.1` only for local development.

`TRUSTED_PROXIES=*` is safe only while the application container is reachable through Coolify's proxy rather than directly from the internet. It ensures Laravel sees the original HTTPS scheme and client IP.

Generate `APP_KEY` once with `php artisan key:generate --show`. Never regenerate it after production data exists because encrypted application data depends on it.

## First deployment

Open the application terminal in Coolify and run:

```bash
php artisan migrate --force
php artisan app:production-check
php artisan optimize
```

Do not seed demo users in production. Configure the Paystack webhook as `https://booktala.example/booking/webhook`.

## Subsequent deployments

Before deploying, take a PostgreSQL backup. After the new container is healthy, run:

```bash
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan app:production-check
```

Use Coolify's rollback to restore the previous image. A database rollback must use a tested backup when a migration is not backward compatible; do not run `migrate:rollback` blindly on production data.

## Backups and monitoring

- Keep daily PostgreSQL backups off the Contabo server and test restoration regularly.
- Back up the persistent storage volume when local storage is used.
- Monitor `/up`, container restarts, queue failures (`php artisan queue:failed`), disk usage, PostgreSQL connections, and backup freshness.
- Keep only one application replica with the bundled scheduler. For horizontal scaling, move the scheduler and queue workers to separate Coolify services.
- Restrict PostgreSQL to Coolify's private network and allow public access only through a temporary IP allowlist when maintenance requires it.

