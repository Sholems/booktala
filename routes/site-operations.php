<?php

use App\Http\Controllers\Operations\InventoryController;
use App\Http\Controllers\Operations\SecurityController;
use Illuminate\Support\Facades\Route;

/*
 * Security and inventory pages shared by the staff and admin portals.
 * Included inside each portal's route group; $portal is 'staff' or 'admin'.
 * Controllers limit staff to their own sites and supervisor-only actions.
 */
Route::get('/security', [SecurityController::class, 'index'])->name($portal.'.security.index');
Route::post('/security/visitors', [SecurityController::class, 'storeVisitor'])->name($portal.'.security.visitors.store');
Route::patch('/security/visitors/{visitor}/out', [SecurityController::class, 'signOutVisitor'])->name($portal.'.security.visitors.out');
Route::post('/security/incidents', [SecurityController::class, 'storeIncident'])->name($portal.'.security.incidents.store');
Route::get('/security/incidents/{incident}', [SecurityController::class, 'showIncident'])->name($portal.'.security.incidents.show');
Route::get('/security/incidents/{incident}/photo', [SecurityController::class, 'incidentPhoto'])->name($portal.'.security.incidents.photo');
Route::patch('/security/incidents/{incident}/resolve', [SecurityController::class, 'resolveIncident'])->name($portal.'.security.incidents.resolve');
Route::patch('/security/sites/{site}/patrol', [SecurityController::class, 'updatePatrolSettings'])->name($portal.'.security.patrol-settings');
Route::post('/security/checkpoints', [SecurityController::class, 'storeCheckpoint'])->name($portal.'.security.checkpoints.store');
Route::patch('/security/checkpoints/{checkpoint}/remove', [SecurityController::class, 'removeCheckpoint'])->name($portal.'.security.checkpoints.remove');
Route::get('/security/checkpoints/print', [SecurityController::class, 'printCheckpoints'])->name($portal.'.security.checkpoints.print');

Route::get('/inventory', [InventoryController::class, 'index'])->name($portal.'.inventory.index');
Route::post('/inventory', [InventoryController::class, 'storeItem'])->name($portal.'.inventory.store');
Route::get('/inventory/{item}', [InventoryController::class, 'show'])->name($portal.'.inventory.show');
Route::patch('/inventory/{item}', [InventoryController::class, 'updateItem'])->name($portal.'.inventory.update');
Route::post('/inventory/{item}/movements', [InventoryController::class, 'storeMovement'])->name($portal.'.inventory.movements.store');
