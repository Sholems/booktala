<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Staff\AttendanceController;
use App\Http\Controllers\Staff\CheckInController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\MaintenanceController;
use App\Http\Controllers\Staff\MessageController;
use App\Http\Controllers\Staff\PatrolController;
use App\Http\Controllers\Staff\PayslipController;
use App\Http\Controllers\Staff\RoomServiceController;
use App\Http\Controllers\Staff\ScheduleController;
use App\Http\Controllers\Staff\TaskController;
use App\Http\Controllers\Staff\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('staff.dashboard');

    Route::get('/tasks', [TaskController::class, 'index'])->name('staff.tasks.index');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('staff.tasks.show');
    Route::patch('/tasks/{task}/start', [TaskController::class, 'start'])->name('staff.tasks.start');
    Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('staff.tasks.complete');
    Route::post('/tasks/{task}/notes', [TaskController::class, 'addNotes'])->name('staff.tasks.notes');

    Route::get('/checkin', [CheckInController::class, 'index'])->name('staff.checkin.index');
    Route::get('/checkin/search', [CheckInController::class, 'search'])->name('staff.checkin.search');
    Route::patch('/checkin/{reservation}/process', [CheckInController::class, 'processCheckIn'])->name('staff.checkin.process');
    Route::get('/checkin/{reservation}/details', [CheckInController::class, 'details'])->name('staff.checkin.details');
    Route::patch('/checkin/{reservation}/verify', [CheckInController::class, 'verifyId'])->name('staff.checkin.verify');

    Route::get('/checkout', [CheckInController::class, 'checkoutIndex'])->name('staff.checkout.index');
    Route::patch('/checkout/{reservation}/process', [CheckInController::class, 'processCheckOut'])->name('staff.checkout.process');

    Route::get('/room-service', [RoomServiceController::class, 'index'])->name('staff.room-service.index');
    Route::patch('/room-service/{order}/advance', [RoomServiceController::class, 'advance'])->name('staff.room-service.advance');
    Route::patch('/room-service/{order}/decline', [RoomServiceController::class, 'decline'])->name('staff.room-service.decline');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('staff.maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('staff.maintenance.store');
    Route::patch('/maintenance/{maintenanceReport}', [MaintenanceController::class, 'update'])->name('staff.maintenance.update');

    Route::get('/schedule', [ScheduleController::class, 'index'])->name('staff.schedule.index');

    Route::get('/messages', [MessageController::class, 'index'])->name('staff.messages.index');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('staff.messages.show');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('staff.messages.reply');

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('staff.attendance.clock-in')->middleware('throttle:10,1');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('staff.attendance.clock-out')->middleware('throttle:10,1');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('staff.notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('staff.notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('staff.notifications.read-all');

    Route::get('/payslips', [PayslipController::class, 'index'])->name('staff.payslips.index');
    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('staff.payslips.show');
    Route::get('/payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('staff.payslips.pdf');

    // Security, inventory and QR patrol scans at the staff member's sites.
    $portal = 'staff';
    require __DIR__.'/site-operations.php';
    Route::get('/patrol/{token}', [PatrolController::class, 'show'])->name('staff.patrol.scan');
    Route::post('/patrol/{token}', [PatrolController::class, 'record'])->name('staff.patrol.record')->middleware('throttle:30,1');

    // Supervisors and managers: their sites' team, work, roster and attendance.
    Route::prefix('team')->middleware('supervisor')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('staff.team.index');
        Route::patch('/tasks/{task}/assign', [TeamController::class, 'assignTask'])->name('staff.team.tasks.assign');
        Route::patch('/repairs/{maintenanceReport}/assign', [TeamController::class, 'assignRepair'])->name('staff.team.repairs.assign');
        Route::get('/roster', [TeamController::class, 'roster'])->name('staff.team.roster');
        Route::post('/roster', [TeamController::class, 'storeShift'])->name('staff.team.roster.store');
        Route::patch('/roster/{schedule}/cancel', [TeamController::class, 'cancelShift'])->name('staff.team.roster.cancel');
        Route::get('/attendance', [TeamController::class, 'attendance'])->name('staff.team.attendance');
    });
});
