# BookTala

BookTala is a Laravel booking and hospitality operations platform. It supports public reservations, Paystack payments, customer and owner portals, multi-property staff operations, security, inventory, finance, payroll and reporting.

## Requirements

- PHP 8.3 or later
- Composer
- Node.js 22 or later
- SQLite for local development, or MySQL in deployed environments

## Local setup

```powershell
git clone <repository-url> BookTala
cd BookTala
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve --port=8004
```

Open `http://localhost:8004`.

For concurrent local development processes, run:

```powershell
composer dev
```

## Booking charges

Each new booking includes charges configured in `.env`:

```dotenv
BOOKING_PROCESSING_FEE=2000
BOOKING_CAUTION_FEE=20000
BOOKING_HOLD_MINUTES=15
```

The processing fee is non-refundable. The caution fee is marked as held once the booking is fully paid and moves to the refund queue after checkout. Partially paid bookings must clear their balance before check-in. Unpaid reservation holds expire after the configured payment window.

## Payment configuration

Add Paystack credentials to `.env`:

```dotenv
PAYSTACK_PUBLIC_KEY=pk_xxx
PAYSTACK_SECRET_KEY=sk_xxx
```

Configure the Paystack webhook URL as `/booking/webhook` on the deployed domain.

## Development checks

```powershell
composer test
composer lint
npm run build
```

The same checks run in GitHub Actions.

## Scheduler and queue

Production must invoke Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/booktala && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler sends check-in reminders, checks operational escalations, expires unpaid booking holds and drains the database queue for shared-hosting deployments. On a server that supports long-running processes, use a supervised `php artisan queue:work` process instead.

Inspect failed jobs with `php artisan queue:failed` and retry them with `php artisan queue:retry <id>`.

## Production deployment

Set `APP_ENV=production` and `APP_DEBUG=false`, then run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
```

Monitor `/up`, back up the database and uploaded files, and verify restoration regularly. Telescope is installed for diagnostics; set `TELESCOPE_ENABLED=false` in production unless it is deliberately enabled and access-controlled.

## Local accounts

The local seeders create development accounts, including:

- Email: `admin@booktala.test`
- Password: `Admin@1234`

Never use seeded credentials in production.
