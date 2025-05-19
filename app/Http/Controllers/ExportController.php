<?php

namespace App\Http\Controllers;

use App\Exports\ClientsExport;
use App\Exports\EmployeesExport;
use App\Exports\RolesExport;
use App\Exports\SitesExport;
use App\Exports\UsersExport;
use App\Imports\EmployeesImport;
use App\Imports\RolesImport;
use App\Imports\SitesImport;
use App\Imports\UsersImport;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Site;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class ExportController extends Controller
{
    public function exportClientExcel()
    {
        Excel::download(new ClientsExport, 'clients.xlsx');
    }
    public function importClientExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new Client(), $request->file('import_file'));

        return back()->with('success', 'Clients imported successfully!');
    }
    public function exportSiteExcel()
    {
        return Excel::download(new SitesExport, 'sites.xlsx');
    }
    public function exportClientPdf()
    {
        $clients = Client::all();
        $pdf = Pdf::loadView('exports.clients_pdf', compact('clients'));
        return $pdf->download('clients.pdf');
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
}
