<?php

use App\Http\Controllers\Owner\MessageController;
use App\Http\Controllers\Owner\PortalController;
use Illuminate\Support\Facades\Route;

// Property owners: view properties, statements, and contact Asset Management.
Route::prefix('owner')->middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('owner.dashboard');
    Route::get('/statements', [PortalController::class, 'statements'])->name('owner.statements.index');
    Route::get('/statements/{statement}', [PortalController::class, 'statement'])->name('owner.statements.show');
    Route::get('/statements/{statement}/pdf', [PortalController::class, 'statementPdf'])->name('owner.statements.pdf');

    // Concierge & Inquiries Hub
    Route::get('/messages', [MessageController::class, 'index'])->name('owner.messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('owner.messages.store');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('owner.messages.show');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('owner.messages.reply');
});
