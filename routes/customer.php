<?php

use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\ReservationController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\MessageController;
use App\Http\Controllers\Customer\LoyaltyController;
use App\Http\Controllers\Customer\CheckinController;
use App\Http\Controllers\Customer\RoomServiceController;
use App\Http\Controllers\Customer\EventBookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->middleware(['auth', 'role:customer'])->name('customer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{property}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{property}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('messages.reply');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/upload-id', [ProfileController::class, 'uploadId'])->name('profile.upload-id');
    Route::post('/profile/connect-google', [ProfileController::class, 'connectGoogle'])->name('profile.connect-google');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::post('/reviews/{reservation}', [ReservationController::class, 'submitReview'])->name('reviews.store');

    Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeem'])->name('loyalty.redeem');
    Route::post('/loyalty/{reservation}/apply', [LoyaltyController::class, 'applyDiscount'])->name('loyalty.apply');

    Route::get('/checkin', [CheckinController::class, 'index'])->name('checkin.index');
    Route::get('/checkin/{reservation}/create', [CheckinController::class, 'create'])->name('checkin.create');
    Route::post('/checkin/{reservation}', [CheckinController::class, 'store'])->name('checkin.store');
    Route::get('/checkin/{checkin}', [CheckinController::class, 'show'])->name('checkin.show');

    Route::get('/room-service', [RoomServiceController::class, 'index'])->name('room-service.index');
    Route::get('/room-service/create', [RoomServiceController::class, 'create'])->name('room-service.create');
    Route::post('/room-service', [RoomServiceController::class, 'store'])->name('room-service.store');
    Route::get('/room-service/{order}', [RoomServiceController::class, 'show'])->name('room-service.show');
    Route::post('/room-service/{order}/cancel', [RoomServiceController::class, 'cancel'])->name('room-service.cancel');

    Route::get('/event-bookings', [EventBookingController::class, 'index'])->name('event-bookings.index');
    Route::get('/event-bookings/create', [EventBookingController::class, 'create'])->name('event-bookings.create');
    Route::post('/event-bookings', [EventBookingController::class, 'store'])->name('event-bookings.store');
    Route::get('/event-bookings/{booking}', [EventBookingController::class, 'show'])->name('event-bookings.show');
    Route::post('/event-bookings/{booking}/cancel', [EventBookingController::class, 'cancel'])->name('event-bookings.cancel');
});