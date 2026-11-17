#!/bin/bash
# Laravel cPanel Deployment Script
# Run this script in your local environment before uploading to cPanel

echo "========================================="
echo "  Laravel cPanel Deployment Prep"
echo "========================================="

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer not found. Please install Composer first."
    exit 1
fi

# Get the project directory name
PROJECT_DIR=$(basename $(pwd))
echo "Project: $PROJECT_DIR"

# Step 1: Install dependencies
echo ""
echo "Step 1: Installing dependencies..."
composer install --optimize-autoloader --no-dev

# Front-end assets (Tailwind, Alpine, Font Awesome, Quill, Chart.js) are bundled by Vite into public/build.
echo ""
echo "Building front-end assets..."
npm ci && npm run build || { echo "Error: front-end build failed."; exit 1; }

# Step 2: Clear and optimize
echo ""
echo "Step 2: Clearing caches..."
php artisan config:cache
php artisan route:clear
php artisan view:clear
php artisan clear-compiled

# Step 3: Generate optimized class loader
echo ""
echo "Step 3: Optimizing autoloader..."
composer dump-autoload -o

# Step 4: Generate application key if not set
echo ""
echo "Step 4: Checking application key..."
php artisan key:generate

# Step 5: Create deployment package
echo ""
echo "Step 5: Creating deployment package..."

# Create deploy directory
mkdir -p deploy

# Copy main files to deploy folder (excluding development files)
rsync -av --progress \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude 'vendor' \
    --exclude '.env' \
    --exclude '.env.example' \
    --exclude 'storage' \
    --exclude '.gitignore' \
    --exclude 'README.md' \
    --exclude 'package.json' \
    --exclude 'package-lock.json' \
    --exclude 'webpack.mix.js' \
    --exclude 'vite.config.js' \
    --exclude '.npm' \
    --exclude '.cache' \
    --exclude '.idea' \
    --exclude '.vscode' \
    --exclude '.DS_Store' \
    --exclude '*.log' \
    --exclude 'deploy' \
    --exclude 'bootstrap/cache/*' \
    . deploy/

# Copy vendor folder (production optimized)
echo ""
echo "Step 6: Copying vendor folder..."
cp -r vendor deploy/

# Copy storage folder structure
echo ""
echo "Step 7: Setting up storage folder..."
mkdir -p deploy/storage/{app/public,framework/{cache,sessions,views},logs}
chmod -R 775 deploy/storage
chmod -R 775 deploy/bootstrap

# Copy bootstrap/cache placeholder
mkdir -p deploy/bootstrap/cache
touch deploy/bootstrap/cache/.gitignore

# Copy .env.example as .env for deployment
if [ ! -f deploy/.env ]; then
    cp .env.example deploy/.env
fi

# Create public/index.php for cPanel (root level)
echo ""
echo "Step 8: Creating cPanel index.php..."
cat > deploy/public/index.php << 'EOF'
<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);
EOF

# Create deployment guide
echo ""
echo "Step 9: Creating deployment instructions..."

# Create cPanel deployment guide
cat > deploy/DEPLOYMENT-GUIDE.md << 'EOF'
# cPanel Deployment Guide

## Quick Deploy (Recommended)

1. **Upload to cPanel File Manager:**
   - Navigate to `public_html/`
   - Upload all files from the `public/` folder to `public_html/`
   - Upload the entire project folder (except public/) to your home directory

2. **Set Folder Permissions:**
   ```
   storage/          → 755
   bootstrap/cache/  → 755
   ```

3. **Create Database:**
   - Go to "MySQL Databases" in cPanel
   - Create a new database (e.g., `yourname_laravel`)
   - Create a user and add to database
   - Import your database.sql file

4. **Update .env:**
   - Edit `.env` file with your cPanel database credentials:
   ```
   DB_HOST=localhost
   DB_DATABASE=yourname_laravel
   DB_USERNAME=yourname_dbuser
   DB_PASSWORD=yourpassword
   ```

5. **Important cPanel Settings:**
   - PHP Version: PHP 8.2 or 8.3
   - Extension: enable "mbstring", "curl", "json", "zip"

6. **Run Commands via SSH or cPanel Terminal:**
   ```bash
   php artisan config:cache
   php artisan migrate --force
   php artisan storage:link
   ```

## Alternative: One-Click Deploy (If supported)

If your cPanel has "Git™ Version Control":
1. Connect your repository
2. Set deployment path to `public_html`
3. Enable auto-deployment

## Troubleshooting

### 500 Internal Server Error
- Check `.env` file exists and is valid
- Set `storage/` and `bootstrap/cache/` permissions to 755

### White Screen
- Run: `php artisan cache:clear`
- Check PHP version is 8.2+

### Database Connection Error
- Verify database credentials in `.env`
- Make sure DB user has full permissions

## Support
For issues, check Laravel logs at `storage/logs/`
EOF

# Zip the deployment package
echo ""
echo "Step 10: Creating ZIP file..."
cd deploy
zip -r ../${PROJECT_DIR}-cpanel-deploy.zip .
cd ..

# Cleanup
rm -rf deploy

echo ""
echo "========================================="
echo "  Deployment Package Ready!"
echo "========================================="
echo ""
echo "Output: ${PROJECT_DIR}-cpanel-deploy.zip"
echo ""
echo "Contents:"
echo "  - All Laravel files"
echo "  - Vendor folder (production)"
echo "  - Storage folder structure"
echo "  - Modified index.php for cPanel"
echo "  - DEPLOYMENT-GUIDE.md"
echo ""
echo "Next Steps:"
echo "1. Upload zip to cPanel"
echo "2. Extract to your hosting folder"
echo "3. Follow DEPLOYMENT-GUIDE.md"
echo ""