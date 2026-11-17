<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    
    Route::get('forgot-password', [PasswordController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    
    Route::get('password/reset/{token}', [PasswordController::class, 'showResetPassword'])->name('password.reset');
    Route::post('password/reset', [PasswordController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('email/verify', [VerificationController::class, 'showVerify'])->name('verification.notice');
    Route::post('email/resend', [VerificationController::class, 'sendVerification'])->name('verification.send');
});

Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify')->middleware('signed');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});