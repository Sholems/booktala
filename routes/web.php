<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\FrontDeskReservationController;
use App\Http\Controllers\OtaFeedController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PropertyController;
use App\Http\Controllers\Public\StorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/media/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('public.media');
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('public.storage');

Route::get('/availability/check', [AvailabilityController::class, 'check'])->name('availability.check')->middleware('throttle:availability');
Route::get('/availability/calendar/{propertyId}', [AvailabilityController::class, 'getCalendar'])->name('availability.calendar')->middleware('throttle:availability');
Route::get('/ota/feed/{token}.ics', [OtaFeedController::class, 'export'])->name('ota.feed');

Route::prefix('front-desk')->middleware(['auth', 'role:super_admin|admin|staff', 'can:reservation.create'])->group(function () {
    Route::get('/reservations/create', [FrontDeskReservationController::class, 'create'])->name('frontdesk.reservations.create');
    Route::get('/reservations/{reservation}/receipt', [FrontDeskReservationController::class, 'receipt'])->name('frontdesk.reservations.receipt');
    Route::get('/guests', [FrontDeskReservationController::class, 'guests'])->name('frontdesk.guests.index');
    Route::post('/reservations/quote', [FrontDeskReservationController::class, 'quote'])->name('frontdesk.reservations.quote');
    Route::post('/reservations', [FrontDeskReservationController::class, 'store'])->name('frontdesk.reservations.store');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
    Route::get('forgot-password', [PasswordController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('password/reset/{token}', [PasswordController::class, 'showResetPassword'])->name('password.reset');
    Route::post('password/reset', [PasswordController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/apartments', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/apartments/{slug}', [PropertyController::class, 'show'])->name('properties.show');
Route::post('/booking/process', [BookingController::class, 'process'])->name('booking.process')->middleware('throttle:booking');
Route::get('/booking/callback', [BookingController::class, 'callback'])->name('booking.callback')->middleware('throttle:payment');
Route::post('/booking/webhook', [BookingController::class, 'webhook'])->name('booking.webhook')->middleware('throttle:payment');
Route::get('/booking/confirmation/{booking_ref}', [BookingController::class, 'confirmation'])->name('booking.confirmation');
Route::get('/booking/{slug}', [BookingController::class, 'show'])->name('booking.show');

Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('pages.contact.submit')->middleware('throttle:contact');
Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/cancellation-policy', [PageController::class, 'cancellation'])->name('pages.cancellation');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
require __DIR__.'/customer.php';
require __DIR__.'/owner.php';

Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
