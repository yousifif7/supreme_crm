<?php

use App\Models\User;
use App\Models\ShiftDate;
use App\Models\BookingAlarm;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\TrainingMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DobController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\API\TrainingController;
use App\Http\Controllers\AlertReminderController;
use App\Http\Controllers\API\CheckCallController;
use App\Http\Controllers\EmployeeLeaveController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\SubContractorController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\API\LocationAPIController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\ShiftNotificationController;
use App\Http\Controllers\VehicleComplianceController;
use App\Http\Controllers\VehicleMaintenanceController;
use App\Http\Controllers\DocumentationUploadController;
use App\Http\Controllers\RoadworthinessCheckController;

Route::get('/', function () {
    return view('auth.login');
});
/*
Route::get('/dashboard', function () {
    //return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/
Route::get('/generate-heatmap', [ShiftController::class, 'generateContinuousPath']);


Route::group(['middleware' => ['auth']], function () {
    // Chat routes
    Route::post('/api/conversations/{id}/pin', [ChatController::class, 'togglePin']);

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/conversations', [ChatController::class, 'createConversation'])->name('conversations');
    Route::get('/load/conversations', [ChatController::class, 'getConversations'])->name('load.conversations');
    Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages'])->name('conversations.messages');
    Route::post('/conversations/{conversationId}/send-messages', [ChatController::class, 'sendMessage'])->name('conversations.sendMessage');
    Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage'])->name('messages.delete');
    Route::get('/conversations/{conversationId}/members', [ChatController::class, 'viewMembers'])->name('conversations.members');
    Route::post('/conversations/{conversationId}/pin', [ChatController::class, 'togglePin'])->name('conversations.togglePin');
    Route::post('/create-one-to-one-conversation', [ChatController::class, 'createOneToOneConversation'])->name('create.one.to.one');
    Route::post('/conversations/{conversationId}/typing', [ChatController::class, 'userTyping'])->name('conversations.typing');
    Route::post('/conversations/{conversationId}/mark-as-read', [ChatController::class, 'markMessagesAsRead'])->name('conversations.markAsRead');
    Route::get('/conversations/{conversationId}/media', [ChatController::class, 'getConversationMedia'])->name('conversations.media');
});


Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationsController::class, 'index'])->name('notifications.index');

    // JSON endpoint for web (session-authenticated) poller
    Route::get('/json', function(Request $request) {
        $user = auth()->user();
        // Admin-like roles see system notifications (legacy uses user_id=1)
        if ($user->hasAnyRole(['superadmin','controller','staff_leader','control_room'])) {
            $notifications = \App\Models\Notification::where('user_id', 1)
                ->orderBy('created_at', 'desc')
                ->limit($request->input('limit', 25))
                ->get();
        } else {
            $employee = \App\Models\Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $notifications = \App\Models\Notification::where('employee_id', $employee->id)
                    ->orWhere('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit($request->input('limit', 25))
                    ->get();
            } else {
                $notifications = \App\Models\Notification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit($request->input('limit', 25))
                    ->get();
            }
        }

        return response()->json(['notifications' => $notifications]);
    })->middleware('auth');

    // One-time pruning endpoint: permanently delete users with deleted_at set.
    // Protect with a token: set PRUNE_DELETED_USERS_TOKEN in .env and call /prune-deleted-users?token=THE_TOKEN


    // Mark a notification as read via web session
    Route::post('/{id}/read', function($id) {
        $notif = \App\Models\Notification::findOrFail($id);
        $notif->update(['read' => true]);
        return response()->json(['message' => 'Notification marked as read']);
    })->middleware('auth');

    // Bulk actions
    Route::post('/mark-as-read', [NotificationsController::class, 'bulkMarkAsRead'])->name('notifications.bulkMarkAsRead');
    Route::post('/delete', [NotificationsController::class, 'bulkDelete'])->name('notifications.bulkDelete');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        // If we're currently impersonating, restore the impersonator (admin) instead of fully logging out
        if ($request->session()->has('impersonator_id')) {
            $impersonatorId = $request->session()->pull('impersonator_id');
            $returnUrl = $request->session()->pull('impersonator_return_url', '/');
            Auth::loginUsingId($impersonatorId);
            return redirect($returnUrl);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');


    Route::get('documents/report', [DocumentController::class, 'report'])->name('documents.report');

    Route::get('incident_report', [IncidentReportController::class, 'index'])->name('incident_report.index');
    Route::get('/incident_report/export/excel', [IncidentReportController::class, 'exportIncidentExcel'])->name('incident_report.export.excel');

    Route::get('/incident_report/export/pdf', [IncidentReportController::class, 'exportIncidentPdf'])->name('incident_report.export.pdf');

Route::post('/shifts/{id}/unassign', [ShiftController::class, 'unassign'])->name('shifts.unassign');


    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::post('/leaves/bulk-delete', [EmployeeLeaveController::class, 'bulkDelete'])->name('leaves.bulkDelete');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/logout', [ProfileController::class, 'logout'])->name('logout');

    /** Begin Employee Controller */
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/editemployee/{id}', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::delete('/deleteemployee/{id}', [EmployeeController::class, 'delete'])->name('employees.delete');
    Route::post('/updateemployee/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('employee.bulkDelete');
    Route::get('/employees/{id}/logs/ajax', [EmployeeController::class, 'getLogs'])->name('employees.logs.ajax');
    Route::get('/employees/logs/{email}', [EmployeeController::class, 'getLogsByEmail'])->name('employees.logs.email')->where('email', '.*');
    Route::get('/employees/{id}/view', [EmployeeController::class, 'view'])->name('employees.view');
    Route::get('/employees/print/{id}', [EmployeeController::class, 'print'])->name('employees.print');

    // Documents AJAX endpoints (used by employee modal)
    Route::get('/documents/user/{userId}/ajax', [DocumentController::class, 'byUser'])->name('documents.byUser');
    Route::post('/employees/{id}/documents/approve', [DocumentController::class, 'approveByEmployee'])->name('employees.documents.approve');
    Route::post('/employees/{id}/documents/reject', [DocumentController::class, 'rejectByEmployee'])->name('employees.documents.reject');

    Route::get('/employees/export/excel', [ExportController::class, 'exportEmployeeExcel'])->name('employees.export.excel');
    Route::get('/employees/export/pdf', [ExportController::class, 'exportEmployeePdf'])->name('employees.export.pdf');
    Route::post('/employees/import', [ExportController::class, 'importEmployeeExcel'])->name('employees.import');
    /** End Employee Controller */

    /** Begin: Subcontractor Controller */
    Route::get('/subcontractors', [SubContractorController::class, 'index'])->name('subcontractors.index');
    Route::post('subcontractors', [SubContractorController::class, 'store'])->name('subcontractors.store');
    Route::get('/editsubcontractor/{id}', [SubContractorController::class, 'edit'])->name('subcontractors.edit');
    Route::delete('/deletesubcontractor/{id}', [SubContractorController::class, 'delete'])->name('subcontractors.delete');
    Route::post('/updatesubcontractor/{id}', [SubContractorController::class, 'update'])->name('subcontractors.update');
    Route::post('/subcontractors/bulk-delete', [SubContractorController::class, 'bulkDelete'])->name('subcontractors.bulkDelete');
    Route::get('/subcontractors/{id}/logs/ajax', [SubContractorController::class, 'getLogs'])->name('subcontractors.logs.ajax');
    Route::get('/subcontractors/{id}/view', [SubContractorController::class, 'view'])->name('subcontractors.view');

    Route::get('/subcontractors/export/excel', [ExportController::class, 'exportSubcontractorExcel'])->name('subcontractors.export.excel');
    Route::get('/subcontractors/export/pdf', [ExportController::class, 'exportSubcontractorPdf'])->name('subcontractors.export.pdf');
    Route::post('/subcontractors/import', [ExportController::class, 'importSubcontractorExcel'])->name('subcontractors.import');
    /** End: Subcontractor COntroller */

    /** Begin: Client Controller */
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/editclient/{id}', [ClientController::class, 'edit'])->name('clients.edit');
    Route::delete('/deleteclient/{id}', [ClientController::class, 'delete'])->name('clients.delete');
    Route::post('/updateclient/{id}', [ClientController::class, 'update'])->name('clients.update');
    Route::post('/clients/bulk-delete', [ClientController::class, 'bulkDelete'])->name('clients.bulkDelete');
    Route::get('/clients/{id}/logs/ajax', [ClientController::class, 'getLogs'])->name('clients.logs.ajax');
    Route::get('/clients/{id}/view', [ClientController::class, 'view'])->name('clients.view');
    Route::post('/clients/{id}/assign-manager', [ClientController::class, 'assignManager'])->name('clients.assignManager');

    Route::get('/clients/export/excel', [ExportController::class, 'exportClientExcel'])->name('clients.export.excel');
    Route::get('/clients/export/pdf', [ExportController::class, 'exportClientPdf'])->name('clients.export.pdf');
    Route::post('/clients/import', [ExportController::class, 'importClientExcel'])->name('clients.import');
    /**  End: Client Controller */

    /** Begin: Impersonation (admin -> client) */
    // Start impersonation (admin or superadmin only)
    Route::group(['middleware' => ['auth', 'role:admin|superadmin']], function() {
        // constrain clientId to digits so the literal '/impersonate/leave' URL doesn't get matched by this route
        Route::get('/impersonate/{clientId}', [App\Http\Controllers\ImpersonationController::class, 'start'])
            ->where('clientId', '[0-9]+')
            ->name('impersonate.start');
    });

    // Stop impersonation (available to any authenticated user while impersonating)
    Route::get('/impersonate/leave', [App\Http\Controllers\ImpersonationController::class, 'stop'])->name('impersonate.stop')->middleware('auth');
    /** End: Impersonation */

    /** Begin: Client-facing dashboard and management (client role) */
    Route::group(['prefix' => 'client', 'as' => 'client.', 'middleware' => ['auth', 'role:client']], function() {
        Route::get('/dashboard', [App\Http\Controllers\ClientDashboardController::class, 'index'])->name('dashboard');
        Route::get('/rota', [App\Http\Controllers\ClientDashboardController::class, 'rota'])->name('rota');

        // Client invoices (scoped)
        Route::get('/invoices', [App\Http\Controllers\ClientInvoicesController::class, 'index'])->name('invoices.index');

        // Client sites management
        Route::get('/sites', [App\Http\Controllers\ClientSiteController::class, 'index'])->name('sites.index');
        Route::get('/sites/create', [App\Http\Controllers\ClientSiteController::class, 'create'])->name('sites.create');
        Route::post('/sites', [App\Http\Controllers\ClientSiteController::class, 'store'])->name('sites.store');
        Route::get('/sites/{id}/edit', [App\Http\Controllers\ClientSiteController::class, 'edit'])->name('sites.edit');
        Route::post('/sites/{id}', [App\Http\Controllers\ClientSiteController::class, 'update'])->name('sites.update');
        Route::get('/sites/{id}', [App\Http\Controllers\ClientSiteController::class, 'show'])->name('sites.show');
    // Client profile (view & update own client record)
    Route::get('/profile', [App\Http\Controllers\ClientDashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [App\Http\Controllers\ClientDashboardController::class, 'updateProfile'])->name('profile.update');
    });
    /** End: Client-facing */
    /** Begin: Invoice Controller  */
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    Route::get('/generateinvoice/{id}', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::post('/generateinvoice/{id}', [InvoiceController::class, 'generateClientInvoice'])->name('invoices.store');

    Route::post('/generateinvoice-sub/{id}', [InvoiceController::class, 'generateSubcontractorInvoice'])->name('invoices.sub');

    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::delete('/deleteinvoice/{id}', [InvoiceController::class, 'delete'])->name('invoices.delete');
    Route::post('/invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])->name('invoices.bulkDelete');
    /** End: Invoice Controller */

    /** Begin: Payroll Controller  */
    Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');

    // JSON endpoint for subcontractor payrolls (used by client-side DataTable)
    Route::get('/payrolls/subcontractor/data', [PayrollController::class, 'subcontractorData'])->name('payrolls.subcontractor.data');


    Route::get('/generatepayroll/{id}', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::post('/generatepayroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::post('/generatepayroll_subcontractor/{id}', [PayrollController::class, 'payrollSubcontractor'])->name('payroll.generatepayroll_subcontractor');


    Route::get('/payrolls/{id}', [PayrollController::class, 'show'])->name('payrolls.show');
    Route::delete('/deletepayroll/{id}', [PayrollController::class, 'delete'])->name('payrolls.delete');
    Route::post('/payrolls/bulk-delete', [PayrollController::class, 'bulkDelete'])->name('payrolls.bulkDelete');
    /** End: Payroll Controller */

    /** Begin: Site Controller  */
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/editsite/{id}', [SiteController::class, 'edit'])->name('sites.edit');
    Route::delete('/deletesite/{id}', [SiteController::class, 'delete'])->name('sites.delete');
    Route::post('/updatesite/{id}', [SiteController::class, 'update'])->name('sites.update');
    Route::post('/sites/bulk-delete', [SiteController::class, 'bulkDelete'])->name('sites.bulkDelete');
    Route::get('/sites/{id}/logs/ajax', [SiteController::class, 'getLogs'])->name('sites.logs.ajax');
    Route::get('/sites/{id}/view', [SiteController::class, 'view'])->name('sites.view');
    Route::post('/sites/{id}/generate-qr', [SiteController::class, 'generateQr'])->name('sites.generateQr');

    Route::get('/sites/export/excel', [ExportController::class, 'exportSiteExcel'])->name('sites.export.excel');
    Route::get('/sites/export/pdf', [ExportController::class, 'exportSitePdf'])->name('sites.export.pdf');
    Route::post('/sites/import', [ExportController::class, 'importSiteExcel'])->name('sites.import');
    /** End: Site Controller */


    Route::get('settings/restrictions', [SettingController::class, 'index'])->name('restrictions.index');
    Route::post('settings/restrictions/{id}/toggle', [SettingController::class, 'toggle'])->name('restrictions.toggle');



    /** Begin: Shift Controller */
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/scheduling', [ShiftController::class, 'scheduling'])->name('shifts.scheduling');
    Route::get('/worker_calendar', [ShiftController::class, 'worker_calendar'])->name('shifts.worker_calendar');
    Route::get('/site_calendar', [ShiftController::class, 'site_calendar'])->name('shifts.site_calendar');
    Route::get('/today_rota', [ShiftController::class, 'today_rota'])->name('shifts.today_rota');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/updateshift/{id}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::post('/updateshift/simple/{id}', [ShiftController::class, 'updateSimple'])->name('shifts.updateSimple');
    Route::get('/editshift/{id}', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::delete('/deleteshift/{id}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
    Route::post('/shifts/bulk-delete', [ShiftController::class, 'bulkDelete'])->name('shifts.bulkDelete');
    Route::get('/shifts/{shiftDate}', [ShiftController::class, 'show'])->name('shifts.show');
    Route::get('/api/shifts', [ShiftController::class, 'getShifts']);
    Route::get('/api/shifts-with-staff', [ShiftController::class, 'getShiftsWithStaff']);
    Route::get('/api/shifts-by-site', [ShiftController::class, 'getShiftsBySite']);
    Route::get('/api/shifts-today', [ShiftController::class, 'getTodayShifts']);
    Route::post('/shifts/filter', [ShiftController::class, 'filter'])->name('shifts.filter');
    Route::post('/check-calls/{id}/status', [ShiftController::class, 'updateStatus'])->name('checkcalls.updateStatus');
    Route::post('/check-calls/{id}/comment', [ShiftController::class, 'addComment'])->name('checkcalls.addComment');

    Route::post('/shifts/multi-assign', [ShiftController::class, 'multiAssign'])
        ->name('shifts.multi-assign');

    Route::post('/shifts/multi-edit', [ShiftController::class, 'multiEdit'])->name('shifts.multiEdit');

    Route::get('/shift-dates/{shiftDate}/view', [ShiftController::class, 'view'])
        ->name('shiftDates.view');

    Route::put('/checkcalls/{id}', [CheckCallController::class, 'update']);
    Route::delete('/checkcalls/{id}', [CheckCallController::class, 'destroy']);

    Route::get('shifts/{sd_id}', [ShiftController::class, 'showShiftModal']);

    Route::post('/book-records/{id}/acknowledge', [UserController::class, 'acknowledge'])->name('bookrecords.acknowledge');

    Route::get('show/acknowledged/{id}', [TrainingController::class, 'showAcknowledged'])->name('show.acknowledged');

    Route::post('/shift/bookon/store', [ShiftController::class, 'storeBookon'])->name('shift.bookon.store');
    Route::post('/shift/bookoff/store', [ShiftController::class, 'storeBookoff'])->name('shift.bookoff.store');

    Route::get('/api/client/{id}', [ShiftController::class, 'getClient']);
    Route::get('/api/staff/{id}', [ShiftController::class, 'getStaff']);

    Route::get('/shifts/stats', [ShiftController::class, 'getMonthlyShiftsStats'])->name('getMonthlyShiftsStats');
    Route::post('/assign-shift', [ShiftController::class, 'assign'])->name('shifts.assign');

    /** Begin: Holiday Controller */
    Route::get('/leaves', [EmployeeLeaveController::class, 'index'])->name('leaves.index');
    Route::post('/leaves', [EmployeeLeaveController::class, 'store'])->name('leaves.store');
    Route::put('/leaves/{id}', [EmployeeLeaveController::class, 'update'])->name('leaves.update');
    Route::get('/editleave/{id}', [EmployeeLeaveController::class, 'edit'])->name('leaves.edit');
    Route::delete('/deleteleave/{id}', [EmployeeLeaveController::class, 'destroy'])->name('leaves.destroy');
    Route::get('/leaves/{id}/logs/ajax', [EmployeeLeaveController::class, 'getLogs'])->name('leaves.logs.ajax');
    Route::get('/leaves/{id}/view', [EmployeeLeaveController::class, 'view'])->name('leaves.view');

    /** End: Holiday Controller */


    /** Begin: User Controller */
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::get('/edituser/{id}', [UserController::class, 'edit'])->name('users.edit');
    Route::delete('/deleteuser/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{id}/logs/ajax', [UserController::class, 'getLogs'])->name('users.logs.ajax');
    Route::get('/users/{id}/view', [UserController::class, 'view'])->name('users.view');

    /** End: User Controller */

    /** Begin: Role Controller */
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit']);
    Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/deleterole/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/bulk-delete', [RoleController::class, 'bulkDelete'])->name('roles.bulkDelete');
    /** End: Role Controller */

    /** Begin: Permission controller */
    Route::resource('permissions', PermissionController::class);
    Route::delete('/deletepermission/{id}', [PermissionController::class, 'destroy']);
    Route::post('/permissions/bulk-delete', [PermissionController::class, 'bulkDelete'])->name('permissions.bulkDelete');
    /** End: Permission Controller */

    /** Begin Vehicle controller  */
    Route::get('/vehicle_details', [VehicleController::class, 'vehicle_details'])->name('vehicle_details');
    Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/editvehicle/{id}', [VehicleController::class, 'edit'])->name('vehicles.edit');
    Route::delete('/deletevehicle/{id}', [VehicleController::class, 'delete'])->name('vehicles.delete');
    Route::post('/updatevehicle/{id}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::post('/vehicles/bulk-delete', [VehicleController::class, 'bulkDelete'])->name('vehicles.bulkDelete');
    /** End: Vehicle Controller */


    /** Begin: documentation upload controller */
    Route::get('/documentation_uploads/data', [DocumentationUploadController::class, 'data'])->name('documents.data');
    Route::get('/documentation_uploads', [DocumentationUploadController::class, 'index'])->name('documents');
    Route::post('documentation_uploads', [DocumentationUploadController::class, 'store'])->name('documents.store');
    Route::get('/editdocument/{id}', [DocumentationUploadController::class, 'edit'])->name('documents.edit');
    Route::delete('/deletedocument/{id}', [DocumentationUploadController::class, 'delete'])->name('documents.delete');
    Route::post('/updatedocument/{id}', [DocumentationUploadController::class, 'update'])->name('documents.update');
    Route::post('/documents/bulk-delete', [DocumentationUploadController::class, 'bulkDelete'])->name('documents.bulkDelete');

    Route::get('documents/report', [DocumentController::class, 'report'])->name('documents.report');

    //** End: documentation upload controller */
    Route::get('/weekly-hours-alerts', [UserController::class, 'weeklyHoursNotification']);


    Route::get('/vehicle_management', [VehicleController::class, 'management'])
        ->name('vehicle.management');

    Route::get('/vehicle_data', [VehicleController::class, 'vehicle_details_data'])
        ->name('vehicle.data');
});

Route::get('/materials/export/excel', [TrainingController::class, 'exportMaterialsExcel'])->name('materials.export.excel');
Route::get('/materials/export/pdf', [TrainingController::class, 'exportMaterialsPdf'])->name('materials.export.pdf');

Route::prefix('leaves')->group(function () {
    Route::get('pending', [EmployeeLeaveController::class, 'pending'])->name('leaves.pending');
    Route::post('approve/{leave}', [EmployeeLeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('reject/{leave}', [EmployeeLeaveController::class, 'reject'])->name('leaves.reject');
});

Route::post('/hr/store', [TrainingController::class, 'store'])->name('materials.store');
Route::get('/hr', [TrainingController::class, 'matsView'])->name('materials.index');
Route::post('/hr/bulkdelete', [TrainingController::class, 'bulkDelete'])->name('materials.bulkDelete');
Route::get('/calendar', [EmployeeLeaveController::class, 'calendar'])->name('calendar');

Route::put('materials/{id}', [TrainingController::class, 'update'])->name('materials.update');
// Delete single material
Route::delete('materials/{id}', [TrainingController::class, 'destroy'])->name('materials.destroy');
// Bulk delete
Route::get('materials/{id}', [TrainingController::class, 'show'])->name('materials.show');

// web.php
Route::get('/shift/{shiftId}/map', [ShiftController::class, 'map'])->name('shift.map');

Route::get('/invoices/export/excel', [ExportController::class, 'exportInvoiceExcel'])->name('invoices.export.excel');
Route::get('/invoices/export/pdf', [ExportController::class, 'exportInvoicePdf'])->name('invoices.export.pdf');

Route::get('/leaves/export/excel', [ExportController::class, 'exportLeaveExcel'])->name('leaves.export.excel');
Route::get('/leaves/export/pdf', [ExportController::class, 'exportLeavePdf'])->name('leaves.export.pdf');

Route::get('/users/export/excel', [ExportController::class, 'exportUserExcel'])->name('users.export.excel');
Route::get('/users/export/pdf', [ExportController::class, 'exportUserPdf'])->name('users.export.pdf');
Route::post('/users/import', [ExportController::class, 'importUserExcel'])->name('users.import');

Route::get('/roles/export/excel', [ExportController::class, 'exportRoleExcel'])->name('roles.export.excel');
Route::get('/roles/export/pdf', [ExportController::class, 'exportRolePdf'])->name('roles.export.pdf');
Route::post('/roles/import', [ExportController::class, 'importRoleExcel'])->name('roles.import');

Route::get('/vehicles/export/excel', [ExportController::class, 'exportVehicleExcel'])->name('vehicles.export.excel');
Route::get('/vehicles/export/pdf', [ExportController::class, 'exportVehiclePdf'])->name('vehicles.export.pdf');
Route::post('/vehicles/import', [ExportController::class, 'importVehicleExcel'])->name('vehicles.import');

Route::get('/compliances/export/excel', [ExportController::class, 'exportComplianceExcel'])->name('compliances.export.excel');
Route::post('/compliances/import', [ExportController::class, 'importComplianceExcel'])->name('compliances.import');
Route::get('/compliances/export/pdf', [ExportController::class, 'exportCompliancePdf'])->name('compliances.export.pdf');

Route::get('/maintenances/export/excel', [ExportController::class, 'exportMaintenanceExcel'])->name('maintenances.export.excel');
Route::post('/maintenances/import', [ExportController::class, 'importMaintenanceExcel'])->name('maintenances.import');
Route::get('/maintenances/export/pdf', [ExportController::class, 'exportMaintenancePdf'])->name('maintenances.export.pdf');

Route::get('/checks/export/excel', [ExportController::class, 'exportCheckExcel'])->name('checks.export.excel');
Route::post('/checks/import', [ExportController::class, 'importCheckExcel'])->name('checks.import');
Route::get('/checks/export/pdf', [ExportController::class, 'exportCheckPdf'])->name('checks.export.pdf');

Route::get('/reminders/export/excel', [ExportController::class, 'exportReminderExcel'])->name('reminders.export.excel');
Route::get('/reminders/export/pdf', [ExportController::class, 'exportReminderPdf'])->name('reminders.export.pdf');
Route::post('/reminders/import', [ExportController::class, 'importReminderExcel'])->name('reminders.import');


Route::get('/shifts/export/excel', [ExportController::class, 'exportShiftExcel'])->name('shifts.export.excel');
Route::get('/shifts/export/pdf', [ExportController::class, 'exportShiftPdf'])->name('shifts.export.pdf');
Route::post('/shifts/import', [ExportController::class, 'importShiftExcel'])->name('shifts.import');
Route::group(['middleware' => ['role:superadmin|user']], function () {});

// notifications
Route::middleware('auth')->group(function () {
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/mark-selected-read/', [NotificationController::class, 'markSelectedRead'])->name('notifications.markSelectedRead');
});



Route::prefix('incidents')->group(function () {
    Route::get('/', [IncidentReportController::class, 'index'])->name('incidents.index'); // datatable page
    Route::get('/{id}', [IncidentReportController::class, 'show'])->name('incidents.show'); // show details
    Route::get('/{id}/edit', [IncidentReportController::class, 'edit'])->name('incidents.edit');
    Route::put('/{id}', [IncidentReportController::class, 'update'])->name('incidents.update');
    Route::post('/store', [IncidentReportController::class, 'store'])->name('incidents.store');
    Route::delete('/{id}', [IncidentReportController::class, 'destroy'])->name('incidents.destroy'); // delete
    Route::post('bulkdelete', [IncidentReportController::class, 'bulkdelete'])->name('incidents.bulkdelete'); // delete
    Route::post('/{id}/status', [IncidentReportController::class, 'updateStatus'])->name('incidents.updateStatus');
});

Route::prefix('dobs')->group(function () {
    Route::get('/', [DobController::class, 'index'])->name('dobs.index'); // DataTable
    Route::post('/', [DobController::class, 'store'])->name('dobs.store'); // Create
    Route::get('{id}', [DobController::class, 'show'])->name('dobs.show'); // Show modal
    Route::get('{id}/edit', [DobController::class, 'edit'])->name('dobs.edit'); // Edit modal
    Route::put('{id}', [DobController::class, 'update'])->name('dobs.update'); // Update
    Route::delete('{id}', [DobController::class, 'destroy'])->name('dobs.destroy'); // Delete
    Route::post('/bulk-delete', [DobController::class, 'bulkDelete'])->name('dobs.bulkDelete');
});

Route::put('/patrols/{id}', [ShiftController::class, 'patrolUpdate'])->name('patrol.update');
Route::delete('/patrols/{id}', [ShiftController::class, 'patrolDestroy'])->name('patrol.delete');

Route::get('/dobs/export/excel', [DobController::class, 'exportDobExcel'])->name('dobs.export.excel');
Route::get('/dobs/export/pdf', [DobController::class, 'exportDobPdf'])->name('dobs.export.pdf');
Route::get('/patrol/{patrol}/locations', [LocationAPIController::class, 'locations'])
    ->name('patrol.locations');


Route::get('/reports/employment', [EmployeeController::class, 'employmentReport'])->name('reports.employment');
Route::get('/reports/employment/{employee}/pdf', [EmployeeController::class, 'exportEmploymentPdf'])
    ->name('reports.employment.pdf');


Route::post('/assign-shift-override', [ShiftController::class, 'assignWithOverride'])
    ->middleware(['auth', 'can:assign-shift-override'])
    ->name('assign.shift.override');

Route::post('/updateshift/{id}/override', [ShiftController::class, 'updateWithOverride'])
    ->middleware(['auth', 'can:assign-shift-override'])
    ->name('assign.shift.override');

Route::post('/shifts/multi-assign-override', [ShiftController::class, 'multiAssignWithOverride'])
    ->middleware(['auth', 'can:assign-shift-override'])
    ->name('assign.shift.override');

Route::post('/shifts/store-override', [ShiftController::class, 'storeOverride'])
    ->middleware('auth')
    ->name('shifts.store.override');

Route::get('/shift-dates/{id}/note', [ShiftController::class, 'showNote'])->name('shift.note.show');
Route::post('/shift-dates/{id}/note', [ShiftController::class, 'storeNote'])->name('shift.note.store');
Route::delete('/shift-dates/{id}/note', [ShiftController::class, 'deleteNote'])->name('shift.note.delete');

Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

Route::get('/reports/availability', [ReportController::class, 'availabilityReport'])->name('availability.report');
Route::get('/reports/performance', [ReportController::class, 'performanceReport'])->name('performance.report');
Route::get('/staff-report', [ReportController::class, 'staffReport'])->name('staff.report');
Route::get('/booking/report', [ReportController::class, 'bookingReport'])->name('booking.report');
Route::get('/reports/shifts', [ReportController::class, 'shiftReport'])
    ->name('reports.shift');
Route::get('/reports/clients', [ReportController::class, 'clientReport'])
    ->name('reports.clients');

Route::get('/reports/checkpoints', [ReportController::class, 'checkpointReport'])->name('report.checkpoints');    
Route::get('/reports/salary', [ReportController::class, 'salaryReport'])->name('salary.report');    

Route::get('/reports/clients/export/pdf', [ReportController::class, 'exportClientReportPDF'])->name('client.report.export.pdf');
Route::get('/reports/clients/export/excel', [ReportController::class, 'exportClientReportExcel'])->name('client.report.export.excel');

require __DIR__ . '/auth.php';

require __DIR__ . '/docs.php';

// Web-trigger for shift notifications (controller-based). Authenticated users only.
Route::post('/process-shift-notifications', [ShiftNotificationController::class, 'process'])
    ->middleware('auth')
    ->name('process.shift.notifications');
