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

Start from `.env.production.example` (production mode, debug off, Telescope off, MySQL, secure cookies), fill in the blanks, then run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
php artisan app:production-check
```

Site settings are cached and refreshed automatically when changed in the admin; if you ever edit the `settings` table directly, run `php artisan cache:clear`.

`app:production-check` fails if debug is on, Telescope is recording, the database is SQLite (it ignores the row locks used for bookings, payments and stock), or the app key is missing, and warns about mail, queue, https and cookie settings.

Monitor `/up`, back up the database and uploaded files (including `storage/app/private`, which holds guest ID documents, receipts and incident photos), and verify restoration regularly. Telescope never records in production, whatever `TELESCOPE_ENABLED` says.

## Local accounts

The local seeders create development accounts, including:

- Email: `admin@booktala.test`
- Password: `Admin@1234`

Never use seeded credentials in production.
