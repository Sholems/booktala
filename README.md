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

The production container runs Laravel's scheduler and queue worker under Supervisor. The scheduler sends check-in reminders, checks operational escalations, expires unpaid booking holds, synchronizes OTA calendars, and matures affiliate commissions. Scheduled tasks use shared cache locks so only one replica executes each task.

Inspect failed jobs with `php artisan queue:failed` and retry them with `php artisan queue:retry <id>`.

## Production deployment

Deploy the repository Dockerfile through Coolify and connect it to PostgreSQL on Coolify's private network. Start from `.env.production.example`, fill in the blanks, and follow [the Coolify deployment guide](docs/deployment/coolify-postgresql.md).

For a release check inside the running container:

```bash
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan app:production-check
```

Site settings are cached and refreshed automatically when changed in the admin; if you ever edit the `settings` table directly, run `php artisan cache:clear`.

`app:production-check` fails unless production mode, PostgreSQL, Paystack, outbound mail, queued jobs, HTTPS, secure encrypted sessions, and private/public R2 isolation are configured safely.

Monitor `/up`, back up the database and uploaded files (including `storage/app/private`, which holds guest ID documents, receipts and incident photos), and verify restoration regularly. Telescope never records in production, whatever `TELESCOPE_ENABLED` says.

## Local accounts

The local seeders create development accounts, including:

- Email: `admin@booktala.test`
- Password: `Admin@1234`

Never use seeded credentials in production.
