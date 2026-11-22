<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AffiliateManagementController;
use App\Http\Controllers\Admin\MarketingCampaignController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\ChannelManagerController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventBookingController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FacilityMaintenanceController;
use App\Http\Controllers\Admin\FinanceLedgerController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\HotelAmenityController;
use App\Http\Controllers\Admin\HousekeepingInspectionController;
use App\Http\Controllers\Admin\IcalController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\ManagementAgreementController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\OwnerStatementController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoomServiceController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\SiteReportController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffScheduleController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TaxComplianceController;
use App\Http\Controllers\Admin\WhatsAppController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Profile & Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('admin.profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('admin.profile.avatar.destroy');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

    Route::resource('properties', PropertyController::class)->names('admin.properties')
        ->middlewareFor(['index', 'show'], 'can:property.view')
        ->middlewareFor(['create', 'store'], 'can:property.create')
        ->middlewareFor(['edit', 'update'], 'can:property.edit')
        ->middlewareFor('destroy', 'can:property.delete');
    Route::patch('/properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('admin.properties.status')->middleware('can:property.edit');
    Route::patch('/properties/{property}/featured', [PropertyController::class, 'toggleFeatured'])->name('admin.properties.featured')->middleware('can:property.edit');
    Route::post('/properties/bulk-action', [PropertyController::class, 'bulkAction'])->name('admin.properties.bulk-action')->middleware('can:property.delete');

    Route::get('/room-types', [RoomTypeController::class, 'index'])->name('admin.room-types.index')->middleware('can:property.view');
    Route::get('/room-types/create', [RoomTypeController::class, 'create'])->name('admin.room-types.create')->middleware('can:property.view');
    Route::post('/room-types', [RoomTypeController::class, 'store'])->name('admin.room-types.store')->middleware('can:property.edit');
    Route::get('/room-types/{roomType}/edit', [RoomTypeController::class, 'edit'])->name('admin.room-types.edit')->middleware('can:property.view');
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('admin.room-types.update')->middleware('can:property.edit');
    Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('admin.room-types.destroy')->middleware('can:property.edit');
    Route::get('/room-types/{roomType}/rooms', [RoomTypeController::class, 'rooms'])->name('admin.room-types.rooms')->middleware('can:property.view');
    Route::patch('/rooms/{room}/status', [RoomTypeController::class, 'updateRoomStatus'])->name('admin.rooms.update-status')->middleware('can:property.edit');

    Route::get('/hotel-amenities', [HotelAmenityController::class, 'index'])->name('admin.hotel-amenities.index')->middleware('can:property.view');
    Route::get('/hotel-amenities/create', [HotelAmenityController::class, 'create'])->name('admin.hotel-amenities.create')->middleware('can:property.view');
    Route::post('/hotel-amenities', [HotelAmenityController::class, 'store'])->name('admin.hotel-amenities.store')->middleware('can:property.edit');
    Route::get('/hotel-amenities/{hotelAmenity}/edit', [HotelAmenityController::class, 'edit'])->name('admin.hotel-amenities.edit')->middleware('can:property.view');
    Route::put('/hotel-amenities/{hotelAmenity}', [HotelAmenityController::class, 'update'])->name('admin.hotel-amenities.update')->middleware('can:property.edit');
    Route::delete('/hotel-amenities/{hotelAmenity}', [HotelAmenityController::class, 'destroy'])->name('admin.hotel-amenities.destroy')->middleware('can:property.edit');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('admin.reservations.index')->middleware('can:reservation.view');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('admin.reservations.show')->middleware('can:reservation.view');
    Route::patch('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('admin.reservations.confirm')->middleware('can:reservation.manage');
    Route::patch('/reservations/{reservation}/checkin', [ReservationController::class, 'checkIn'])->name('admin.reservations.checkin')->middleware('can:reservation.manage');
    Route::patch('/reservations/{reservation}/checkout', [ReservationController::class, 'checkOut'])->name('admin.reservations.checkout')->middleware('can:reservation.manage');
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('admin.reservations.cancel')->middleware('can:reservation.manage');
    Route::patch('/reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])->name('admin.reservations.no-show')->middleware('can:reservation.manage');
    Route::patch('/reservations/{reservation}/refund', [ReservationController::class, 'updateRefund'])->name('admin.reservations.refund')->middleware('can:reservation.refund');
    Route::patch('/reservations/{reservation}/caution-refund', [ReservationController::class, 'updateCautionRefund'])->name('admin.reservations.caution-refund')->middleware('can:reservation.refund');

    Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers.index')->middleware('can:customer.view');
    Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('admin.customers.show')->middleware('can:customer.view');
    Route::get('/customers/{user}/id-document', [CustomerController::class, 'idDocument'])->name('admin.customers.id-document')->middleware('can:customer.manage');
    Route::patch('/customers/{user}/verify', [CustomerController::class, 'verify'])->name('admin.customers.verify')->middleware('can:customer.manage');
    Route::patch('/customers/{user}/status', [CustomerController::class, 'updateStatus'])->name('admin.customers.status')->middleware('can:customer.manage');

    Route::get('/staff', [StaffController::class, 'index'])->name('admin.staff.index')->middleware('can:staff.manage');
    Route::post('/staff', [StaffController::class, 'store'])->name('admin.staff.store')->middleware('can:staff.manage');
    Route::get('/staff/{user}/edit', [StaffController::class, 'edit'])->name('admin.staff.edit')->middleware('can:staff.manage');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->name('admin.staff.update')->middleware('can:staff.manage');
    Route::put('/staff/{user}/password', [StaffController::class, 'resetPassword'])->name('admin.staff.password')->middleware('can:staff.manage');
    Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('admin.staff.destroy')->middleware('can:staff.manage');
    Route::post('/staff/{user}/assignments', [StaffController::class, 'addAssignment'])->name('admin.staff.assignments.store')->middleware('can:staff.manage');
    Route::patch('/staff/{user}/assignments/{assignment}/end', [StaffController::class, 'endAssignment'])->name('admin.staff.assignments.end')->middleware('can:staff.manage');
    Route::patch('/staff/{user}/assignments/{assignment}/primary', [StaffController::class, 'makePrimary'])->name('admin.staff.assignments.primary')->middleware('can:staff.manage');

    // Multi-property structure: who owns what, where staff work, and what they do.
    Route::resource('owners', OwnerController::class)->except('show')->names('admin.owners')->middleware('can:owner.manage');
    Route::resource('sites', SiteController::class)->except('show')->names('admin.sites')->middleware('can:site.manage');
    Route::post('/sites/{site}/properties', [SiteController::class, 'attachProperty'])->name('admin.sites.properties.attach')->middleware('can:site.manage');
    Route::post('/owners/{owner}/portal', [OwnerController::class, 'portalAccess'])->name('admin.owners.portal')->middleware('can:owner.manage');
    Route::patch('/owners/{owner}/portal/revoke', [OwnerController::class, 'revokePortal'])->name('admin.owners.portal.revoke')->middleware('can:owner.manage');

    // Security and inventory across all sites.
    Route::middleware('can:site.manage')->group(function () {
        $portal = 'admin';
        require __DIR__.'/site-operations.php';
    });
    Route::get('/reports/sites', [SiteReportController::class, 'index'])->name('admin.reports.sites')->middleware('can:report.view');
    Route::get('/reports/sites/export', [SiteReportController::class, 'export'])->name('admin.reports.sites.export')->middleware('can:report.export');

    // Finance: property expenses and owner statements.
    Route::middleware('can:finance.manage')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('admin.expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
        Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('admin.expenses.receipt');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

        Route::get('/agreements', [ManagementAgreementController::class, 'index'])->name('admin.agreements.index');
        Route::get('/agreements/audit', [ManagementAgreementController::class, 'audit'])->name('admin.agreements.audit');
        Route::get('/agreements/create', [ManagementAgreementController::class, 'create'])->name('admin.agreements.create');
        Route::post('/agreements', [ManagementAgreementController::class, 'store'])->name('admin.agreements.store');
        Route::get('/agreements/{agreement}', [ManagementAgreementController::class, 'show'])->name('admin.agreements.show');
        Route::patch('/agreements/{agreement}/review', [ManagementAgreementController::class, 'review'])->name('admin.agreements.review');
        Route::patch('/agreements/{agreement}/retire', [ManagementAgreementController::class, 'retire'])->name('admin.agreements.retire');
        Route::delete('/agreements/{agreement}', [ManagementAgreementController::class, 'destroy'])->name('admin.agreements.destroy');

        Route::get('/statements', [OwnerStatementController::class, 'index'])->name('admin.statements.index');
        Route::post('/statements/generate', [OwnerStatementController::class, 'generate'])->name('admin.statements.generate');
        Route::get('/statements/{statement}', [OwnerStatementController::class, 'show'])->name('admin.statements.show');
        Route::get('/statements/{statement}/pdf', [OwnerStatementController::class, 'pdf'])->name('admin.statements.pdf');
        Route::patch('/statements/{statement}/approve', [OwnerStatementController::class, 'approve'])->name('admin.statements.approve');
        Route::patch('/statements/{statement}/paid', [OwnerStatementController::class, 'markPaid'])->name('admin.statements.paid');
        Route::delete('/statements/{statement}', [OwnerStatementController::class, 'destroy'])->name('admin.statements.destroy');

        // Statutory General Ledger & Accounting Export
        Route::get('/ledger', [FinanceLedgerController::class, 'index'])->name('admin.finance.ledger.index');
        Route::get('/ledger/export', [FinanceLedgerController::class, 'export'])->name('admin.finance.ledger.export');
        Route::get('/ledger/{transaction}', [FinanceLedgerController::class, 'show'])->name('admin.finance.ledger.show');

        // Tax & Withholding Tax (WHT) Compliance Reports (LIRS / FIRS)
        Route::get('/tax-compliance', [TaxComplianceController::class, 'index'])->name('admin.finance.tax.index');
        Route::get('/tax-compliance/export', [TaxComplianceController::class, 'export'])->name('admin.finance.tax.export');
    });

    // Payroll: super admin by default (payroll.manage).
    Route::middleware('can:payroll.manage')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
        Route::post('/payroll', [PayrollController::class, 'generate'])->name('admin.payroll.generate');
        Route::get('/payroll/settings', [PayrollController::class, 'settings'])->name('admin.payroll.settings');
        Route::post('/payroll/settings', [PayrollController::class, 'updateSettings'])->name('admin.payroll.settings.update');
        Route::get('/payroll/runs/{payRun}', [PayrollController::class, 'show'])->name('admin.payroll.show');
        Route::patch('/payroll/runs/{payRun}/approve', [PayrollController::class, 'approve'])->name('admin.payroll.approve');
        Route::patch('/payroll/runs/{payRun}/paid', [PayrollController::class, 'markPaid'])->name('admin.payroll.paid');
        Route::delete('/payroll/runs/{payRun}', [PayrollController::class, 'destroy'])->name('admin.payroll.destroy');
        Route::get('/payroll/runs/{payRun}/bank-schedule', [PayrollController::class, 'bankSchedule'])->name('admin.payroll.bank-schedule');
        Route::get('/payroll/payslips/{payslip}/pdf', [PayrollController::class, 'payslipPdf'])->name('admin.payroll.payslip-pdf');
        Route::put('/payroll/staff/{user}/salary', [PayrollController::class, 'updateSalary'])->name('admin.payroll.salary');
        Route::post('/payroll/staff/{user}/deductions', [PayrollController::class, 'addDeduction'])->name('admin.payroll.deductions.store');
        Route::patch('/payroll/staff/{user}/deductions/{deduction}/stop', [PayrollController::class, 'stopDeduction'])->name('admin.payroll.deductions.stop');
    });
    Route::resource('positions', PositionController::class)->except(['show', 'create'])->names('admin.positions')->middleware('can:staff.manage');

    Route::get('/tasks', [TaskController::class, 'index'])->name('admin.tasks.index')->middleware('can:task.manage');
    Route::post('/tasks', [TaskController::class, 'store'])->name('admin.tasks.store')->middleware('can:task.manage');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('admin.tasks.update')->middleware('can:task.manage');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('admin.tasks.show')->middleware('can:task.manage');
    Route::post('/tasks/{task}/notes', [TaskController::class, 'addNote'])->name('admin.tasks.notes')->middleware('can:task.manage');

    // Housekeeping Inspections & Room Turnover Approval
    Route::get('/inspections', [HousekeepingInspectionController::class, 'index'])->name('admin.inspections.index')->middleware('can:task.manage');
    Route::post('/inspections', [HousekeepingInspectionController::class, 'store'])->name('admin.inspections.store')->middleware('can:task.manage');
    Route::get('/inspections/{inspection}', [HousekeepingInspectionController::class, 'show'])->name('admin.inspections.show')->middleware('can:task.manage');
    Route::patch('/inspections/{inspection}/approve', [HousekeepingInspectionController::class, 'approve'])->name('admin.inspections.approve')->middleware('can:task.manage');
    Route::patch('/inspections/{inspection}/reject', [HousekeepingInspectionController::class, 'reject'])->name('admin.inspections.reject')->middleware('can:task.manage');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('admin.maintenance.index')->middleware('can:maintenance.manage');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('admin.maintenance.store')->middleware('can:maintenance.manage');
    Route::patch('/maintenance/{maintenanceReport}', [MaintenanceController::class, 'update'])->name('admin.maintenance.update')->middleware('can:maintenance.manage');

    // Preventative Facilities & Generator Maintenance
    Route::get('/facilities', [FacilityMaintenanceController::class, 'index'])->name('admin.facilities.index')->middleware('can:maintenance.manage');
    Route::post('/facilities', [FacilityMaintenanceController::class, 'store'])->name('admin.facilities.store')->middleware('can:maintenance.manage');
    Route::patch('/facilities/{schedule}/complete', [FacilityMaintenanceController::class, 'completeService'])->name('admin.facilities.complete')->middleware('can:maintenance.manage');
    Route::get('/facilities/generator-logs', [FacilityMaintenanceController::class, 'generatorLogs'])->name('admin.facilities.generator-logs')->middleware('can:maintenance.manage');
    Route::post('/facilities/generator-logs', [FacilityMaintenanceController::class, 'storeGeneratorLog'])->name('admin.facilities.generator-logs.store')->middleware('can:maintenance.manage');

    Route::get('/room-service', [RoomServiceController::class, 'index'])->name('admin.room-service.index')->middleware('can:reservation.view');
    Route::patch('/room-service/{order}/advance', [RoomServiceController::class, 'advance'])->name('admin.room-service.advance')->middleware('can:reservation.manage');
    Route::patch('/room-service/{order}/decline', [RoomServiceController::class, 'decline'])->name('admin.room-service.decline')->middleware('can:reservation.manage');

    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index')->middleware('can:report.view');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('admin.reviews.index')->middleware('can:review.manage');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('admin.reviews.show')->middleware('can:review.manage');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('admin.reviews.destroy')->middleware('can:review.manage');
    Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('admin.reviews.approve')->middleware('can:review.manage');
    Route::patch('/reviews/{review}/reply', [ReviewController::class, 'reply'])->name('admin.reviews.reply')->middleware('can:review.manage');

    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index')->middleware('can:settings.manage');
    Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update')->middleware('can:settings.manage');
    Route::post('/settings/toggle-mode', [SettingsController::class, 'toggleMode'])->name('admin.settings.toggle-mode')->middleware('can:settings.manage');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('admin.invoices.index')->middleware('can:reservation.view');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('admin.invoices.create')->middleware('can:reservation.view');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('admin.invoices.store')->middleware('can:reservation.manage');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('admin.invoices.show')->middleware('can:reservation.view');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('admin.invoices.pdf')->middleware('can:reservation.view');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('admin.invoices.send')->middleware('can:reservation.manage');
    Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('admin.invoices.mark-paid')->middleware('can:reservation.manage');

    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index')->middleware('can:report.view');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('admin.reports.revenue')->middleware('can:report.view');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('admin.reports.occupancy')->middleware('can:report.view');
    Route::get('/reports/guests', [ReportController::class, 'guests'])->name('admin.reports.guests')->middleware('can:report.view');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('admin.reports.export')->middleware('can:report.export');

    // Channel Manager & OTA Synchronization
    Route::middleware('can:channels.manage')->group(function () {
        Route::get('/channel-manager', [ChannelManagerController::class, 'index'])->name('admin.channel-manager.index');
        Route::post('/channel-manager', [ChannelManagerController::class, 'store'])->name('admin.channel-manager.store');
        Route::post('/channel-manager/mapping', [ChannelManagerController::class, 'storeMapping'])->name('admin.channel-manager.mapping.store');
        Route::patch('/channel-manager/mapping/{mapping}/toggle', [ChannelManagerController::class, 'toggleMapping'])->name('admin.channel-manager.mapping.toggle');
        Route::post('/channel-manager/mapping/{mapping}/sync', [ChannelManagerController::class, 'syncMapping'])->name('admin.channel-manager.mapping.sync');
        Route::post('/channel-manager/sync-all', [ChannelManagerController::class, 'syncAll'])->name('admin.channel-manager.sync-all');
        Route::delete('/channel-manager/mapping/{mapping}', [ChannelManagerController::class, 'destroyMapping'])->name('admin.channel-manager.mapping.destroy');
        Route::patch('/channel-manager/{channel}/toggle', [ChannelManagerController::class, 'toggle'])->name('admin.channel-manager.toggle');
        Route::post('/channel-manager/{channel}/sync', [ChannelManagerController::class, 'sync'])->name('admin.channel-manager.sync');
        Route::delete('/channel-manager/{channel}', [ChannelManagerController::class, 'destroy'])->name('admin.channel-manager.destroy');
    });

    // Dynamic Pricing & Seasonal Rates
    Route::get('/pricing-rules', [PricingRuleController::class, 'index'])->name('admin.pricing-rules.index')->middleware('can:property.view');
    Route::get('/pricing-rules/simulator', [PricingRuleController::class, 'simulator'])->name('admin.pricing-rules.simulator')->middleware('can:property.view');
    Route::post('/pricing-rules', [PricingRuleController::class, 'store'])->name('admin.pricing-rules.store')->middleware('can:property.edit');
    Route::put('/pricing-rules/{pricingRule}', [PricingRuleController::class, 'update'])->name('admin.pricing-rules.update')->middleware('can:property.edit');
    Route::patch('/pricing-rules/{pricingRule}/toggle', [PricingRuleController::class, 'toggle'])->name('admin.pricing-rules.toggle')->middleware('can:property.edit');
    Route::delete('/pricing-rules/{pricingRule}', [PricingRuleController::class, 'destroy'])->name('admin.pricing-rules.destroy')->middleware('can:property.edit');

    Route::get('/ical/reservations', [IcalController::class, 'exportReservations'])->name('admin.ical.reservations')->middleware('can:reservation.view');
    Route::get('/ical/property/{propertyId}', [IcalController::class, 'exportProperty'])->name('admin.ical.property')->middleware('can:reservation.view');
    Route::get('/ical/url', [IcalController::class, 'getIcalUrl'])->name('admin.ical.url')->middleware('can:reservation.view');

    Route::get('/messages', [MessageController::class, 'index'])->name('admin.messages.index')->middleware('can:customer.view');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('admin.messages.show')->middleware('can:customer.view');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('admin.messages.reply')->middleware('can:customer.manage');

    // Automated WhatsApp Notifications & Message Hub
    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('admin.whatsapp.index')->middleware('can:customer.view');
    Route::post('/whatsapp/send', [WhatsAppController::class, 'send'])->name('admin.whatsapp.send')->middleware('can:customer.manage');

    Route::get('/staff-schedules', [StaffScheduleController::class, 'index'])->name('admin.staff-schedules.index')->middleware('can:staff.manage');
    Route::post('/staff-schedules', [StaffScheduleController::class, 'store'])->name('admin.staff-schedules.store')->middleware('can:staff.manage');
    Route::patch('/staff-schedules/{schedule}/status', [StaffScheduleController::class, 'updateStatus'])->name('admin.staff-schedules.update-status')->middleware('can:staff.manage');
    Route::delete('/staff-schedules/{schedule}', [StaffScheduleController::class, 'destroy'])->name('admin.staff-schedules.destroy')->middleware('can:staff.manage');
    Route::get('/staff-schedules/calendar', [StaffScheduleController::class, 'calendar'])->name('admin.staff-schedules.calendar')->middleware('can:staff.manage');

    Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index')->middleware('can:staff.manage');
    Route::get('/attendance/export', [AdminAttendanceController::class, 'export'])->name('admin.attendance.export')->middleware('can:staff.manage');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('admin.notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('admin.notifications.read-all');

    Route::get('/pages', [PageController::class, 'index'])->name('admin.pages.index')->middleware('can:page.manage');
    Route::get('/pages/create', [PageController::class, 'create'])->name('admin.pages.create')->middleware('can:page.manage');
    Route::post('/pages', [PageController::class, 'store'])->name('admin.pages.store')->middleware('can:page.manage');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('admin.pages.edit')->middleware('can:page.manage');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('admin.pages.update')->middleware('can:page.manage');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('admin.pages.destroy')->middleware('can:page.manage');

    Route::get('/blogs', [BlogController::class, 'index'])->name('admin.blogs.index')->middleware('can:page.manage');
    Route::get('/blogs/create', [BlogController::class, 'create'])->name('admin.blogs.create')->middleware('can:page.manage');
    Route::post('/blogs', [BlogController::class, 'store'])->name('admin.blogs.store')->middleware('can:page.manage');
    Route::get('/blogs/{blog}/edit', [BlogController::class, 'edit'])->name('admin.blogs.edit')->middleware('can:page.manage');
    Route::put('/blogs/{blog}', [BlogController::class, 'update'])->name('admin.blogs.update')->middleware('can:page.manage');
    Route::delete('/blogs/{blog}', [BlogController::class, 'destroy'])->name('admin.blogs.destroy')->middleware('can:page.manage');

    Route::get('/homepage-sections', [HomepageSectionController::class, 'index'])->name('admin.homepage-sections.index')->middleware('can:page.manage');
    Route::get('/homepage-sections/{homepageSection}/edit', [HomepageSectionController::class, 'edit'])->name('admin.homepage-sections.edit')->middleware('can:page.manage');
    Route::put('/homepage-sections/{homepageSection}', [HomepageSectionController::class, 'update'])->name('admin.homepage-sections.update')->middleware('can:page.manage');

    Route::get('/event-bookings', [EventBookingController::class, 'index'])->name('admin.event-bookings.index')->middleware('can:reservation.view');
    Route::get('/event-bookings/{booking}', [EventBookingController::class, 'show'])->name('admin.event-bookings.show')->middleware('can:reservation.view');
    Route::patch('/event-bookings/{booking}', [EventBookingController::class, 'update'])->name('admin.event-bookings.update')->middleware('can:reservation.manage');

    Route::get('/event-spaces', [EventBookingController::class, 'spaces'])->name('admin.event-spaces.index')->middleware('can:property.view');
    Route::get('/event-spaces/create', [EventBookingController::class, 'createSpace'])->name('admin.event-spaces.create')->middleware('can:property.view');
    Route::post('/event-spaces', [EventBookingController::class, 'storeSpace'])->name('admin.event-spaces.store')->middleware('can:property.edit');
    Route::get('/event-spaces/{space}/edit', [EventBookingController::class, 'editSpace'])->name('admin.event-spaces.edit')->middleware('can:property.view');
    Route::patch('/event-spaces/{space}', [EventBookingController::class, 'updateSpace'])->name('admin.event-spaces.update')->middleware('can:property.edit');
    Route::delete('/event-spaces/{space}', [EventBookingController::class, 'destroySpace'])->name('admin.event-spaces.destroy')->middleware('can:property.edit');

    // Affiliate & Partner Management
    Route::get('/affiliates', [AffiliateManagementController::class, 'index'])->name('admin.affiliates.index');
    Route::post('/affiliates/default-rate', [AffiliateManagementController::class, 'updateDefaultRate'])->name('admin.affiliates.default-rate');
    Route::get('/affiliates/referrals', [AffiliateManagementController::class, 'referrals'])->name('admin.affiliates.referrals');
    Route::get('/affiliates/payouts', [AffiliateManagementController::class, 'payouts'])->name('admin.affiliates.payouts');
    Route::post('/affiliates/payouts/{payout}/complete', [AffiliateManagementController::class, 'completePayout'])->name('admin.affiliates.payouts.complete');
    Route::post('/affiliates/payouts/{payout}/reject', [AffiliateManagementController::class, 'rejectPayout'])->name('admin.affiliates.payouts.reject');
    Route::get('/affiliates/{affiliate}', [AffiliateManagementController::class, 'show'])->name('admin.affiliates.show');
    Route::put('/affiliates/{affiliate}', [AffiliateManagementController::class, 'update'])->name('admin.affiliates.update');

    // Email Marketing & Campaign Suite
    Route::prefix('marketing')->name('admin.marketing.')->group(function () {
        Route::get('/subscribers', [MarketingCampaignController::class, 'subscribers'])->name('subscribers.index');
        Route::post('/subscribers', [MarketingCampaignController::class, 'storeSubscriber'])->name('subscribers.store');
        Route::patch('/subscribers/{subscriber}/toggle', [MarketingCampaignController::class, 'toggleSubscriber'])->name('subscribers.toggle');
        Route::delete('/subscribers/{subscriber}', [MarketingCampaignController::class, 'destroySubscriber'])->name('subscribers.destroy');

        Route::get('/campaigns', [MarketingCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [MarketingCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [MarketingCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}', [MarketingCampaignController::class, 'show'])->name('campaigns.show');
        Route::get('/campaigns/{campaign}/edit', [MarketingCampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('/campaigns/{campaign}', [MarketingCampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{campaign}', [MarketingCampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::get('/campaigns/{campaign}/preview', [MarketingCampaignController::class, 'preview'])->name('campaigns.preview');
        Route::post('/campaigns/{campaign}/test', [MarketingCampaignController::class, 'sendTest'])->name('campaigns.test');
        Route::post('/campaigns/{campaign}/dispatch', [MarketingCampaignController::class, 'dispatchCampaign'])->name('campaigns.dispatch');
    });
});
