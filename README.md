# BookTala - Short-let Booking & Operations Platform

## Setup Instructions for Laragon

### Prerequisites
- Laragon (with PHP 8.2+, MySQL)
- Composer

### Installation Steps

1. **Clone/Create Project**
   ```
   cd C:\laragon\www
   composer create-project laravel/laravel shortlet_apartment
   cd shortlet_apartment
   ```

2. **Install Dependencies**
   ```bash
   composer require spatie/laravel-permission spatie/laravel-medialibrary spatie/laravel-sluggable artesaos/seotools barryvdh/laravel-dompdf maatwebsite/excel laravel/socialite pragmarx/google2fa-laravel intervention/image laravel/telescope
   ```

   **Front-end assets** (Tailwind 4, Alpine, Font Awesome, Quill, Chart.js) are bundled with Vite:
   ```bash
   npm install
   npm run build   # or `npm run dev` for hot reload while editing views
   ```
   Pages fail with a "Vite manifest not found" error until one of these has run. The deploy scripts run the build for you.

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Setup**
   - Create database `booktala` in MySQL
   - Update `.env` with your database credentials
   
5. **Run Migrations & Seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Default Admin Login**
   - Email: admin@booktala.test
   - Password: Admin@1234

7. **Start Server**
   - Start Laragon and access http://booktala.test

### Configuration

#### Paystack (Payment Gateway)
- Get keys from https://paystack.com
- Add to `.env`:
  ```
  PAYSTACK_PUBLIC_KEY=pk_xxx
  PAYSTACK_SECRET_KEY=sk_xxx
  ```

#### Google OAuth (Optional)
- Get credentials from Google Cloud Console
- Add to `.env`:
  ```
  GOOGLE_CLIENT_ID=xxx
  GOOGLE_CLIENT_SECRET=xxx
  GOOGLE_REDIRECT_URI=http://booktala.test/auth/google/callback
  ```

#### Mail (Mailtrap for local dev)
- Get credentials from https://mailtrap.io
- Update MAIL config in `.env`

### Features Implemented
- User roles & permissions (Spatie)
- Property management with images (Media Library)
- SEO optimization with structured data
- Booking system with Paystack integration
- Pricing calculation with discount codes
- Admin, Staff, Customer dashboards
- Task management system
- Review system
- PDF invoice generation
- Excel export for reports
- Scheduled jobs for reminders

### Cron Jobs (for cPanel)
Add to cPanel Cron Jobs:
```
0 7 * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

### Security Notes
- Update APP_KEY in production
- Use strong database passwords
- Enable HTTPS in production
- Configure Paystack webhook URL in Paystack dashboard
- Set appropriate file upload limits