@echo off
REM Simple Laravel cPanel Deployment Package Creator

echo ==========================================
echo   Deployment Package Creator
echo ==========================================
echo.

if not exist "vendor" (
    echo ERROR: vendor folder not found
    echo This script must be run in your Laravel project folder
    pause
    exit /b 1
)

echo Found vendor folder...
echo.

echo Building front-end assets (Vite: public\build)...
call npm ci && call npm run build || (echo ERROR: front-end build failed. Install Node.js and retry. & pause & exit /b 1)
echo.

echo Step 1: Creating deploy folder...
if exist "deploy" rmdir /s /q deploy
mkdir deploy

echo Step 2: Copying files...
xcopy /E /I /Y /Q . deploy\ 2>nul

echo Step 3: Removing unnecessary files...
del /q deploy\package.json 2>nul
del /q deploy\package-lock.json 2>nul
del /q deploy\.git 2>nul
rmdir /s /q deploy\node_modules 2>nul
rmdir /s /q deploy\.env 2>nul
rmdir /s /q deploy\.idea 2>nul
rmdir /s /q deploy\.vscode 2>nul

echo Step 4: Creating folders...
mkdir deploy\storage\app\public
mkdir deploy\storage\framework\cache\data
mkdir deploy\storage\framework\sessions
mkdir deploy\storage\framework\views
mkdir deploy\storage\logs
mkdir deploy\bootstrap\cache

echo Step 5: Creating index.php for cPanel...
(
echo ^<?php
echo define('LARAVEL_START', microtime(true));
echo if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
echo     require $maintenance;
echo }
echo require __DIR__.'/../vendor/autoload.php';
echo $app = require_once __DIR__.'/../bootstrap/app.php';
echo $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
echo $response = $kernel->handle(Illuminate\Http\Request::capture())->send();
echo $kernel->terminate($kernel->handle(Illuminate\Http\Request::capture()), $response);
) > deploy\public\index.php

echo Step 6: Creating ZIP...
if exist "cpanel-deploy.zip" del /q "cpanel-deploy.zip"
powershell -Command "Compress-Archive -Path 'deploy\*' -DestinationPath 'cpanel-deploy.zip' -Force"

rmdir /s /q deploy

echo.
echo ==========================================
echo   DONE!
echo ==========================================
echo.
echo Output: cpanel-deploy.zip
echo.
echo Upload to cPanel, then:
echo 1. Extract to public_html (public contents)
echo 2. Extract rest to a folder above public_html
echo 3. Set permissions: storage/ and bootstrap/cache/ to 755
echo 4. Update .env with your database info
echo 5. Run: php artisan config:cache
echo.
pause