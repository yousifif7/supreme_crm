<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\ShiftDate;
use Illuminate\Http\Request;
use App\Models\Subcontractor;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\Reports\ShiftReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Reports\StaffReportExport;

class ReportController extends Controller
{
    public function staffReport(Request $request)
    {
        $employeeTypes = $request->input('employee_type', []); // ['security', 'subcontractor']
        $filterDate = $request->input('filter_date');

        $employees = collect();
        $subcontractors = collect();

        // Security Staff filter
        if (in_array('security', $employeeTypes)) {
            $employees = Employee::query()
                ->when($filterDate, fn($q) => $q->whereDate('created_at', '>=', $filterDate))
                ->get()
                ->map(fn($e) => $e->setAttribute('model_type', 'employee'));
        }

        // Subcontractor filter
        if (in_array('subcontractor', $employeeTypes)) {
            $subcontractors = Subcontractor::query()
                ->when($filterDate, fn($q) => $q->whereDate('created_at', '>=', $filterDate)) // using created_at as engagement date
                ->get()
                ->map(fn($s) => $s->setAttribute('model_type', 'subcontractor'));
        }

        // Merge both collections
        $staff = $employees->concat($subcontractors);

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.staff-pdf', [
                'staff' => $staff,
            ]);

            $fileName = 'Staff_Report_' . now()->format('Y_m_d_His') . '.pdf';
            return $pdf->download($fileName);
        }

        if ($request->has('export') && $request->export === 'excel') {
            $fileName = 'Staff_Report_' . now()->format('Y_m_d_His') . '.xlsx';
            return Excel::download(new StaffReportExport($staff), $fileName);
        }

        return view('reports.staff_report', [
            'employees' => $staff,
            'selectedTypes' => $employeeTypes,
            'filterDate' => $filterDate,
        ]);
    }


    public function shiftReport(Request $request)
    {
        $query = ShiftDate::query()->with(['shift.client', 'shift.site', 'shift.staff']);

        // Filter by client
        if ($request->filled('client_id')) {
            $query->whereHas('shift.client', function ($q) use ($request) {
                $q->where('id', $request->input('client_id'));
            });
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('id', $request->input('employee_id'));
            });
        }

        // Filter by date
        if ($request->filled('shift_date')) {
            $query->whereDate('shift_date', $request->input('shift_date'));
        }
        if ($request->filled('status')) {
            $query->whereIn('is_assign', (array) $request->status);
        }

        $shifts = $query->get();

        $statusOptions = [
            0 => 'Pending',
            1 => 'Dispatched',
            2 => 'Accepted',
            3 => 'Started',
            4 => 'Ended',
            5 => 'Rejected',
            6 => 'Cancelled',
            7 => 'Pre-start',
            8 => 'Await-finish',
        ];

        // Dropdowns
        $clients = User::role('client')->pluck('name', 'id');
        $employees = User::role('security_staff')->orderBy('first_name')->get();

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.shift-pdf', [
                'shiftDates' => $shifts,
                'statusOptions' => $statusOptions,
            ]);

            $fileName = 'Shift_Report_' . now()->format('Y_m_d_His') . '.pdf';
            return $pdf->download($fileName);
        }

        if ($request->has('export') && $request->export === 'excel') {
            $fileName = 'Shift_Report_' . now()->format('Y_m_d_His') . '.xlsx';
            return Excel::download(new ShiftReportExport($shifts), $fileName);
        }

        return view('reports.shift', [
            'shifts' => $shifts,
            'clients' => $clients,
            'employees' => $employees,
            'selectedClient' => $request->input('client_id'),
            'selectedEmployee' => $request->input('employee_id'),
            'selectedStatus' => $request->status ?? [],
            'statusOptions' => $statusOptions,
            'filterDate' => $request->input('shift_date'),
        ]);
    }
}
