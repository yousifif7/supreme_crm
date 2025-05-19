<?php

use App\Exports\ClientsExport;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Imports\ClientsImport;
use App\Imports\SitesImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return view('auth.login');
});
/*
Route::get('/dashboard', function () {
    //return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/logout', [ProfileController::class, 'logout'])->name('logout');

    /** Begin Employee Controller */
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/editemployee/{id}', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::delete('/deleteemployee/{id}', [EmployeeController::class, 'delete'])->name('employees.delete');
    Route::post('/updateemployee/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('employee.bulkDelete');
    /** End Employee Controller */

    /** Begin: Client Controller */
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/editclient/{id}', [ClientController::class, 'edit'])->name('clients.edit');
    Route::delete('/deleteclient/{id}', [ClientController::class, 'delete'])->name('clients.delete');
    Route::post('/updateclient/{id}', [ClientController::class, 'update'])->name('clients.update');
    Route::post('/clients/bulk-delete', [ClientController::class, 'bulkDelete'])->name('clients.bulkDelete');
    /**  End: Client Controller */

    /** Begin: Site Controller  */
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/editsite/{id}', [SiteController::class, 'edit'])->name('sites.edit');
    Route::delete('/deletesite/{id}', [SiteController::class, 'delete'])->name('sites.delete');
    Route::post('/updatesite/{id}', [SiteController::class, 'update'])->name('sites.update');
    Route::post('/sites/bulk-delete', [SiteController::class, 'bulkDelete'])->name('sites.bulkDelete');
    /** End: Site Controller */

    /** Begin: Shift Controller */
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/scheduling', [ShiftController::class, 'scheduling'])->name('shifts.scheduling');
    Route::get('/worker_calendar', [ShiftController::class, 'worker_calendar'])->name('shifts.worker_calendar');
    Route::get('/site_calendar', [ShiftController::class, 'site_calendar'])->name('shifts.site_calendar');
    Route::get('/today_rota', [ShiftController::class, 'today_rota'])->name('shifts.today_rota');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/updateshift/{id}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::get('/editshift/{id}', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::get('/api/shifts', [ShiftController::class, 'getShifts']);
    Route::get('/api/shifts-with-staff', [ShiftController::class, 'getShiftsWithStaff']);
    Route::get('/api/shifts-by-site', [ShiftController::class, 'getShiftsBySite']);
    Route::get('/api/shifts-today', [ShiftController::class, 'getTodayShifts']);
    Route::get('/shifts/stats', [ShiftController::class, 'getMonthlyShiftsStats'])->name('getMonthlyShiftsStats');

    /** Begin: User Controller */
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::get('/edituser/{id}', [UserController::class, 'edit'])->name('users.edit');
    Route::delete('/deleteuser/{id}', [UserController::class, 'destroy'])->name('users.destroy');
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
});
Route::get('/clients/export/excel', [ExportController::class, 'exportClientExcel'])->name('clients.export.excel');
Route::get('/clients/export/pdf', [ExportController::class, 'exportClientExcel'])->name('clients.export.pdf');
Route::post('/clients/import', [ExportController::class, 'importClientExcel'])->name('clients.import');

Route::get('/sites/export/excel', [ExportController::class, 'exportSiteExcel'])->name('sites.export.excel');
Route::get('/sites/export/pdf', [ExportController::class, 'exportSitePdf'])->name('sites.export.pdf');
Route::post('/sites/import', [ExportController::class, 'importSiteExcel'])->name('sites.import');

Route::get('/employees/export/excel', [ExportController::class, 'exportEmployeeExcel'])->name('employees.export.excel');
Route::get('/employees/export/pdf', [ExportController::class, 'exportEmployeePdf'])->name('employees.export.pdf');
Route::post('/employees/import', [ExportController::class, 'importEmployeeExcel'])->name('employees.import');

Route::get('/users/export/excel', [ExportController::class, 'exportUserExcel'])->name('users.export.excel');
Route::get('/users/export/pdf', [ExportController::class, 'exportUserPdf'])->name('users.export.pdf');
Route::post('/users/import', [ExportController::class, 'importUserExcel'])->name('users.import');

Route::get('/roles/export/excel', [ExportController::class, 'exportRoleExcel'])->name('roles.export.excel');
Route::get('/roles/export/pdf', [ExportController::class, 'exportRolePdf'])->name('roles.export.pdf');
Route::post('/roles/import', [ExportController::class, 'importRoleExcel'])->name('roles.import');
Route::group(['middleware' => ['role:superadmin|user']], function () {});

require __DIR__ . '/auth.php';
