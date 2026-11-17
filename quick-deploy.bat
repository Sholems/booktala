@echo off
REM Quick Deployment Package (No PHP/Composer needed)

echo ==========================================
echo   Deployment Package Creator
echo ==========================================
echo.

echo Building front-end assets (Vite: public\build)...
call npm ci && call npm run build || (echo ERROR: front-end build failed. Install Node.js and retry. & pause & exit /b 1)
echo.

echo [1/5] Creating deploy folder...
if exist "deploy" rmdir /s /q deploy
mkdir deploy
mkdir deploy\public
mkdir deploy\storage\app\public
mkdir deploy\storage\framework\cache
mkdir deploy\storage\framework\sessions
mkdir deploy\storage\framework\views
mkdir deploy\storage\logs
mkdir deploy\bootstrap\cache

echo [2/5] Copying files...
xcopy /E /I /Y /Q app deploy\app\
xcopy /E /I /Y /Q config deploy\config\
xcopy /E /I /Y /Q database deploy\database\
xcopy /E /I /Y /Q public deploy\public\ 2>nul
xcopy /E /I /Y /Q resources deploy\resources\
xcopy /E /I /Y /Q routes deploy\routes\
xcopy /E /I /Y /Q bootstrap deploy\bootstrap\
xcopy /E /I /Y /Q tests deploy\tests\ 2>nul

echo [3/5] Copying core files...
copy composer.json deploy\composer.json 2>nul
copy artisan deploy\artisan 2>nul
copy .env.example deploy\.env 2>nul
copy .gitignore deploy\.gitignore 2>nul

echo [4/5] Copying vendor (this may take a moment)...
xcopy /E /I /Y /Q vendor deploy\vendor\

echo [5/5] Creating index.php for cPanel...
(
echo ^<?php
echo define('LARAVEL_START', microtime(true));
echo if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) { require $maintenance; }
echo require __DIR__.'/../vendor/autoload.php';
echo $app = require_once __DIR__.'/../bootstrap/app.php';
echo $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
echo $response = $kernel->handle(Illuminate\Http\Request::capture())->send();
echo $kernel->terminate($kernel->handle(Illuminate\Http\Request::capture()), $response);
) > deploy\public\index.php

echo.
echo Package created successfully!
echo.
pause