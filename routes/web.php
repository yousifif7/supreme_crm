<?php

use App\Models\BookingAlarm;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AlertReminderController;
use App\Http\Controllers\EmployeeLeaveController;
use App\Http\Controllers\SubContractorController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\VehicleComplianceController;
use App\Http\Controllers\VehicleMaintenanceController;
use App\Http\Controllers\DocumentationUploadController;
use App\Http\Controllers\RoadworthinessCheckController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ChatController;


Route::get('/', function () {
    return view('auth.login');
});
/*
Route::get('/dashboard', function () {
    //return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/

Route::get('chat',[ChatController::class,'index']);
Route::get('load/conversations', [ChatController::class, 'getConversations']);
Route::post('/conversations', [ChatController::class, 'createConversation'])->name('conversations');


Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages']);


Route::get('/conversations/{conversationId}/members', [ChatController::class, 'viewMembers']);

Route::post('/api/conversations/{id}/pin', [ChatController::class, 'togglePin']);

Route::post('/create-one-to-one-conversation', [ChatController::class, 'createOneToOneConversation']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::logout();
        return redirect('/login');
    })->name('logout');
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
    Route::get('/employees/{id}/view', [EmployeeController::class, 'view'])->name('employees.view');
    Route::get('/employees/print/{id}', [EmployeeController::class, 'print'])->name('employees.print');

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

    /** Begin: Invoice Controller  */
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    Route::get('/generateinvoice/{id}', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::post('/generateinvoice/{id}', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::delete('/deleteinvoice/{id}', [InvoiceController::class, 'delete'])->name('invoices.delete');
    Route::post('/invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])->name('invoices.bulkDelete');
    /** End: Invoice Controller */

    /** Begin: Payroll Controller  */
    Route::get('/generatepayroll/{id}', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::post('/generatepayroll/{id}', [PayrollController::class, 'store'])->name('payroll.store');
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
    Route::get('/shift/{id}', [ShiftController::class, 'modal']);

    Route::post('/book-records/{id}/acknowledge', [UserController::class, 'acknowledge'])->name('bookrecords.acknowledge');

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

    /** Begin: Vehicle compliance controller */
    Route::get('/vehicle_compliances', [VehicleComplianceController::class, 'index'])->name('complainces');
    Route::post('compliances', [VehicleComplianceController::class, 'store'])->name('compliances.store');
    Route::get('/editcompliance/{id}', [VehicleComplianceController::class, 'edit'])->name('compliances.edit');
    Route::delete('/deletecompliance/{id}', [VehicleComplianceController::class, 'delete'])->name('compliances.delete');
    Route::post('/updatecompliance/{id}', [VehicleComplianceController::class, 'update'])->name('compliances.update');
    Route::post('/compliances/bulk-delete', [VehicleComplianceController::class, 'bulkDelete'])->name('compliances.bulkDelete');
    /** End: Vehicle Maintainace controller */

    /** Begin: Vehicle Maintainance */
    Route::get('/vehicle_maintenances', [VehicleMaintenanceController::class, 'index'])->name('maintenances');
    Route::post('maintenances', [VehicleMaintenanceController::class, 'store'])->name('maintenances.store');
    Route::get('/editmaintenance/{id}', [VehicleMaintenanceController::class, 'edit'])->name('maintenances.edit');
    Route::delete('/deletemaintenance/{id}', [VehicleMaintenanceController::class, 'delete'])->name('maintenances.delete');
    Route::post('/updatemaintenance/{id}', [VehicleMaintenanceController::class, 'update'])->name('maintenances.update');
    Route::post('/maintenances/bulk-delete', [VehicleMaintenanceController::class, 'bulkDelete'])->name('maintenances.bulkDelete');
    /** End: Vehicle Maintainance */

    /** Begin: roadworthinessCheck controller */
    Route::get('/roadworthiness_check', [RoadworthinessCheckController::class, 'index'])->name('checks');
    Route::post('checks', [RoadworthinessCheckController::class, 'store'])->name('checks.store');
    Route::get('/editcheck/{id}', [RoadworthinessCheckController::class, 'edit'])->name('checks.edit');
    Route::delete('/deletecheck/{id}', [RoadworthinessCheckController::class, 'delete'])->name('checks.delete');
    Route::post('/updatecheck/{id}', [RoadworthinessCheckController::class, 'update'])->name('checks.update');
    Route::post('/checks/bulk-delete', [RoadworthinessCheckController::class, 'bulkDelete'])->name('checks.bulkDelete');
    //** End: roadworthinessCheck controller */

    /** Begin: documentation upload controller */
    Route::get('/documentation_uploads', [DocumentationUploadController::class, 'index'])->name('documents');
    Route::post('documents', [DocumentationUploadController::class, 'store'])->name('documents.store');
    Route::get('/editdocument/{id}', [DocumentationUploadController::class, 'edit'])->name('documents.edit');
    Route::delete('/deletedocument/{id}', [DocumentationUploadController::class, 'delete'])->name('documents.delete');
    Route::post('/updatedocument/{id}', [DocumentationUploadController::class, 'update'])->name('documents.update');
    Route::post('/documents/bulk-delete', [DocumentationUploadController::class, 'bulkDelete'])->name('documents.bulkDelete');
    //** End: documentation upload controller */

    /** Begin: alert and remainder controller */
    Route::get('/alert_reminders', [AlertReminderController::class, 'index'])->name('reminders');
    Route::post('reminders', [AlertReminderController::class, 'store'])->name('reminders.store');
    Route::get('/editreminder/{id}', [AlertReminderController::class, 'edit'])->name('reminders.edit');
    Route::delete('/deletereminder/{id}', [AlertReminderController::class, 'delete'])->name('reminders.delete');
    Route::post('/updatereminder/{id}', [AlertReminderController::class, 'update'])->name('reminders.update');
    Route::post('/reminders/bulk-delete', [AlertReminderController::class, 'bulkDelete'])->name('reminders.bulkDelete');
    //** End: alert and remainder controller */
});

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


require __DIR__ . '/auth.php';

// Route::get('/test-read-update', function () {
//     Notification::where('user_id', Auth::id())
//         ->where('read', false)
//         ->update(['read' => true]);

//     return 'done';
// });
