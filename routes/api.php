<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth-api');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth-api');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::get('/properties/{id}/availability', [PropertyController::class, 'availability']);
    Route::post('/properties/{id}/book', [PropertyController::class, 'book'])->middleware('throttle:booking');

    Route::get('/my-reservations', [PropertyController::class, 'myReservations']);

    Route::middleware('role:super_admin|admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/admin/reservations', [AdminController::class, 'reservations']);
        Route::patch('/admin/reservations/{id}', [AdminController::class, 'updateReservation']);
        Route::get('/admin/properties', [AdminController::class, 'properties']);
        Route::post('/admin/properties', [AdminController::class, 'createProperty']);
        Route::patch('/admin/properties/{id}', [AdminController::class, 'updateProperty']);
        Route::delete('/admin/properties/{id}', [AdminController::class, 'deleteProperty']);
        Route::get('/admin/customers', [AdminController::class, 'customers']);
        Route::get('/admin/payments', [AdminController::class, 'payments']);
    });
});
