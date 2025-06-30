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
    public function exportSitePdf()
    {
        $sites = Site::all();
        $pdf = Pdf::loadView('exports.sites_pdf', compact('sites'));
        return $pdf->download('sites.pdf');
    }
    public function importSiteExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new SitesImport, $request->file('import_file'));

        return back()->with('success', 'Sites imported successfully!');
    }
    public function exportEmployeeExcel()
    {
        return Excel::download(new EmployeesExport, 'employees.xlsx');
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


    public function exportUserExcel()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }

    public function exportUserPdf()
    {
        $users = User::all();
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

    public function exportShiftExcel()
    {
        return Excel::download(new ShiftDateExport, 'shifts.xlsx');
    }

    public function exportShiftPdf()
    {
        $shifts = ShiftDate::with('shift')->get();

        $pdf = PDF::loadView('exports.shifts_pdf', compact('shifts'));
        return $pdf->download('shifts.pdf');
    }

    public function importShiftExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new ShiftDateImport, $request->file('file'));

        return back()->with('success', 'Shift imported successfully.');
    }
}
