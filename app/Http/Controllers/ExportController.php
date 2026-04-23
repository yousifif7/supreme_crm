<?php

namespace App\Http\Controllers;

use App\Exports\AlertReminderExport;
use App\Exports\ClientsExport;
use App\Exports\EmployeesExport;
use App\Exports\RolesExport;
use App\Exports\ShiftDateExport;
use App\Exports\InvoiceExport;
use App\Exports\SitesExport;
use App\Exports\SubcontractorsExport;
use App\Exports\LeavesExport;
use App\Exports\UsersExport;
use App\Exports\VehicleComplianceExport;
use App\Exports\VehicleMaintenanceExport;
use App\Exports\VehiclesExport;
use App\Imports\AlertReminderImport;
use App\Imports\ClientsImport;
use App\Imports\EmployeesImport;
use App\Imports\RolesImport;
use App\Imports\ShiftDateImport;
use App\Imports\SitesImport;
use App\Imports\SubcontractorsImport;
use App\Imports\UsersImport;
use App\Imports\VehicleComplianceImport;
use App\Imports\VehicleMaintenanceImport;
use App\Imports\VehiclesImport;
use App\Models\AlertReminder;
use App\Models\Client;
use App\Models\Employee;
use App\Models\RoadworthinessCheck;
use App\Models\ShiftDate;
use App\Models\Invoice;
use App\Models\Site;
use App\Models\Subcontractor;
use App\Models\EmployeeLeave;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCompliance;
use App\Models\VehicleMaintenance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class ExportController extends Controller
{
    // XLSX keeps a cell collection in memory; above this threshold export as CSV.
    private const SHIFT_XLSX_MAX_ROWS = 35000;
    private const SHIFT_PDF_MAX_ROWS = 10000;

    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function buildShiftExportQuery(Request $request, array $ids = [])
    {
        $query = ShiftDate::query()
            ->with(['shift.client', 'shift.site', 'staff'])
            ->select('shift_dates.*')
            ->orderBy('shift_date');

        if (!empty($ids)) {
            $query->whereIn('shift_dates.id', $ids);
        }

        $staffId = $request->input('staff');
        $clientId = $request->input('client_id');
        $siteId = $request->input('site');
        $fromShift = $request->input('from_shift');
        $toShift = $request->input('to_shift');

        if ($staffId !== null && $staffId !== '') {
            $query->where('shift_dates.staff_id', $staffId);
        }

        if ($siteId !== null && $siteId !== '') {
            $query->whereHas('shift', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        if ($clientId !== null && $clientId !== '') {
            $query->whereHas('shift', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        if (!empty($fromShift)) {
            $query->whereDate('shift_dates.shift_date', '>=', $fromShift);
        }

        if (!empty($toShift)) {
            $query->whereDate('shift_dates.shift_date', '<=', $toShift);
        }

        $status = $request->get('ShiftStatus', $request->get('shift_status', $request->get('shiftStatus', $request->get('status'))));
        if ($status !== null && $status !== '') {
            $query->where('shift_dates.is_assign', $status);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('shift_dates.shift_date', 'like', "%{$search}%")
                    ->orWhere('shift_dates.total_hours', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($staffQ) use ($search) {
                        $staffQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shift.client', function ($clientQ) use ($search) {
                        $clientQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shift.site', function ($siteQ) use ($search) {
                        $siteQ->where('site_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function validateShiftExportCount(int $count, int $limit, string $type)
    {
        if ($count <= 0) {
            return back()->with('warning', 'There is no shift data to export for the current filters.');
        }

        if ($count > $limit) {
            return back()->with('error', "Unable to export {$count} shifts as {$type}. Maximum allowed is {$limit}. Please apply tighter filters or select fewer rows.");
        }

        return null;
    }

    public function exportClientExcel(Request $request)
    {
        $isTemplate = $request->has('template') && $request->get('template') == 1;

        if ($isTemplate) {
            return Excel::download(new ClientsExport(true), 'clients_template.xlsx');
        }

        return Excel::download(new ClientsExport(false), 'clients.xlsx');
    }
    public function importClientExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ClientsImport(), $request->file('import_file'));

        return back()->with('success', 'Clients imported successfully!');
    }

    /**
     * Start Subcontractor Export/Import
     */
    public function exportSubcontractorExcel(Request $request)
    {
        $isTemplate = $request->has('template') && $request->get('template') == 1;

        if ($isTemplate) {
            return Excel::download(new SubcontractorsExport(true), 'subcontractors_template.xlsx');
        }

        return Excel::download(new SubcontractorsExport(false), 'subcontractors.xlsx');
    }

    public function exportSubcontractorPdf()
    {
        $subcontractors = Subcontractor::all();
        $pdf = Pdf::loadView('exports.subcontractors_pdf', compact('subcontractors'));
        return $pdf->download('subcontractors.pdf');
    }

    public function importSubcontractorExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls' // further validations are handled in the import class
        ]);

        Excel::import(new SubcontractorsImport(), $request->file('import_file'));

        return back()->with('success', 'Subcontractors imported successfully!');
    }
    /**
     * End Subcontractor Export/Import
     */

    public function exportSiteExcel(Request $request)
    {
        $isTemplate = $request->has('template') && $request->get('template') == 1;

        if ($isTemplate) {
            return Excel::download(new SitesExport(true), 'sites_template.xlsx');
        }

        return Excel::download(new SitesExport(false), 'sites.xlsx');
    }
    public function exportClientPdf()
    {
        $clients = Client::all();
        $pdf = Pdf::loadView('exports.clients_pdf', compact('clients'));
        return $pdf->download('clients.pdf');
    }
    public function exportInvoicePdf()
    {
        $invoices = Invoice::with(['client','site'])->get();
        $pdf = Pdf::loadView('exports.invoices_pdf', compact('invoices'));
        return $pdf->download('invoices.pdf');
    }
    public function exportInvoiceExcel()
    {
        return Excel::download(new InvoiceExport, 'invoices.xlsx');
    }

    public function importSiteExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new SitesImport, $request->file('import_file'));

        return back()->with('success', 'Sites imported successfully!');
    }
    public function exportEmployeeExcel(Request $request)
    {
        $isTemplate = $request->has('template') && $request->get('template') == 1;

        if ($isTemplate) {
            return Excel::download(new EmployeesExport(true), 'employees_template.xlsx');
        }

        return Excel::download(new EmployeesExport(false), 'employees.xlsx');
    }

    public function exportEmployeePdf()
    {
        $employees = Employee::all();
        $pdf = Pdf::loadView('exports.employees_pdf', compact('employees'));
        return $pdf->download('employees.pdf');
    }

    public function importEmployeeExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new EmployeesImport, $request->file('import_file'));

        return back()->with('success', 'Employees imported successfully!');
    }

    public function exportLeaveExcel()
    {
        return Excel::download(new LeavesExport, 'leave.xlsx');
    }

    public function exportLeavePdf()
    {
        $leaves = LeaveRequest::all();
        $pdf = Pdf::loadView('exports.leaves_pdf', compact('leaves'));
        return $pdf->download('leaves.pdf');
    }

    public function exportUserExcel()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }

    public function exportUserPdf()
    {
        $users = User::whereDoesntHave('roles', function($query) {
            $query->whereIn('name', ['client', 'subcontractor', 'security_staff']);
        })->get();
        $pdf = Pdf::loadView('exports.users_pdf', compact('users'));
        return $pdf->download('users.pdf');
    }

    public function importUserExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new UsersImport, $request->file('import_file'));

        return back()->with('success', 'Users imported successfully!');
    }

    public function exportRoleExcel()
    {
        return Excel::download(new RolesExport, 'roles.xlsx');
    }

    public function exportRolePdf()
    {
        $roles = Role::all();
        $pdf = Pdf::loadView('exports.roles_pdf', compact('roles'));
        return $pdf->download('roles.pdf');
    }

    public function importRoleExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new RolesImport, $request->file('import_file'));

        return back()->with('success', 'Roles imported successfully!');
    }

    public function exportVehicleExcel()
    {
        return Excel::download(new VehiclesExport, 'vehicles.xlsx');
    }

    public function exportVehiclePdf()
    {
        $vehicles = Vehicle::all();
        $pdf = PDF::loadView('exports.vehicles_pdf', compact('vehicles'));
        return $pdf->download('vehicles.pdf');
    }

    public function importVehicleExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new VehiclesImport, $request->file('import_file'));

        return redirect()->back()->with('success', 'Vehicles imported successfully.');
    }

    public function exportComplianceExcel()
    {
        return Excel::download(new VehicleComplianceExport, 'vehicle_compliances.xlsx');
    }

    public function importComplianceExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new VehicleComplianceImport, $request->file('import_file'));

        return back()->with('success', 'Vehicle compliance records imported successfully.');
    }
    public function exportCompliancePdf()
    {
        $compliances = VehicleCompliance::all();
        $pdf = PDF::loadView('exports.compliances_pdf', compact('compliances'));
        return $pdf->download('vehicle_compliances.pdf');
    }

    public function exportMaintenanceExcel()
    {
        return Excel::download(new VehicleMaintenanceExport, 'vehicle_maintenances.xlsx');
    }

    public function importMaintenanceExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new VehicleMaintenanceImport, $request->file('import_file'));

        return back()->with('success', 'Vehicle Maintenance records imported successfully.');
    }
    public function exportMaintenancePdf()
    {
        $maintenances = VehicleMaintenance::all();
        $pdf = PDF::loadView('exports.maintenances_pdf', compact('maintenances'));
        return $pdf->download('vehicle_maintenances.pdf');
    }

    public function exportCheckExcel()
    {
        return Excel::download(new RoadworthinessCheck(), 'vehicle_checks.xlsx');
    }

    public function importCheckExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new RoadworthinessCheck(), $request->file('import_file'));

        return back()->with('success', 'Vehicle check records imported successfully.');
    }
    public function exportCheckPdf()
    {
        $checks = RoadworthinessCheck::all();
        $pdf = PDF::loadView('exports.checks_pdf', compact('checks'));
        return $pdf->download('vehicle_checks.pdf');
    }


    public function exportReminderExcel()
    {
        return Excel::download(new AlertReminderExport, 'alert_reminders.xlsx');
    }

    public function exportReminderPdf()
    {
        $reminders = AlertReminder::with('vehicle')->get();

        $pdf = PDF::loadView('exports.alerts_reminders_pdf', compact('reminders'));
        return $pdf->download('alert_reminders.pdf');
    }

    public function importReminderExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new AlertReminderImport, $request->file('file'));

        return back()->with('success', 'Reminders imported successfully.');
    }

    public function exportShiftExcel(Request $request)
    {
        $isTemplate = $request->has('template') && $request->template == 1;

        if ($isTemplate) {
            return Excel::download(new ShiftDateExport(true), 'shifts_template.xlsx');
        }

        // Allow unlimited rows; ShiftDateExport uses FromQuery + chunked processing
        // so memory stays constant regardless of result set size.
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // Reduce PhpSpreadsheet memory pressure by persisting cell cache in batches.
        config([
            'excel.exports.chunk_size' => 500,
            'excel.cache.driver' => 'batch',
            'excel.cache.batch.memory_limit' => 16000,
            'excel.cache.illuminate.store' => config('cache.default'),
        ]);

        $ids   = $this->normalizeIds($request->input('ids'));
        $query = $this->buildShiftExportQuery($request, $ids);
        $count = (clone $query)->count();

        try {
            // CSV is dramatically more memory efficient for very large exports
            // and still opens directly in Excel.
            if ($count > self::SHIFT_XLSX_MAX_ROWS) {
                return Excel::download(
                    new ShiftDateExport(false, $query),
                    'shifts.csv',
                    \Maatwebsite\Excel\Excel::CSV,
                    [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]
                );
            }

            return Excel::download(new ShiftDateExport(false, $query), 'shifts.xlsx');
        } catch (\Throwable $e) {
            \Log::error('Shift Excel export failed: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'rows'  => $count,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function exportShiftPdf(Request $request)
    {
        $ids = $this->normalizeIds($request->input('ids'));
        $query = $this->buildShiftExportQuery($request, $ids);
        $count = (clone $query)->count();

        $validationResponse = $this->validateShiftExportCount($count, self::SHIFT_PDF_MAX_ROWS, 'PDF');
        if ($validationResponse) {
            return $validationResponse;
        }

        $shifts = $query->get();

        $pdf = PDF::loadView('exports.shifts_pdf', compact('shifts'));
        return $pdf->download('shifts.pdf');
    }

    public function importShiftExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new ShiftDateImport;
        Excel::import($import, $request->file('file'));

        $failures = $import->getFailures();
        $successCount = $import->getSuccessCount();
        $failureCount = $import->getFailureCount();

        if (!empty($failures)) {
            $errorMessage = "Import completed: {$successCount} successful, {$failureCount} failed.<br><br>";
            $errorMessage .= '<strong>Failed rows:</strong><br>';
            foreach ($failures as $failure) {
                $errorMessage .= "• Row {$failure['row']}: {$failure['error']}<br>";
            }

            if ($successCount > 0) {
                return back()->with('warning', $errorMessage);
            } else {
                return back()->with('error', $errorMessage);
            }
        }

        return back()->with('success', "All {$successCount} shifts imported successfully.");
    }
}
