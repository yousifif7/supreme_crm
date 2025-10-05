<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\ShiftBooking;
use Illuminate\Http\Request;
use App\Models\Subcontractor;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PatrolCheckPoint;
use App\Exports\Reports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Reports\ShiftReportExport;
use App\Exports\Reports\StaffReportExport;
use App\Exports\Reports\ClientReportExport;
use App\Exports\Reports\BookingReportExport;

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

    public function clientReport(Request $request)
    {
        $query = Client::query()->with(['company', 'manager']);

        // Keyword Search
        if ($request->filled('search')) {
            $keyword = $request->input('search');
            $query->where(function ($q) use ($keyword) {
                $q->where('client_name', 'like', "%$keyword%")
                    ->orWhere('contact_person', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%");
            });
        }

        // Company filter
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Manager filter
        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        // Contract start/end filters
        if ($request->filled('contract_start')) {
            $query->whereDate('contract_start', '>=', $request->contract_start);
        }
        if ($request->filled('contract_end')) {
            $query->whereDate('contract_end', '<=', $request->contract_end);
        }

        // Status filter (active or expired)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereDate('contract_end', '>=', now());
            } elseif ($request->status === 'expired') {
                $query->whereDate('contract_end', '<', now());
            }
        }

        $clients = $query->get();

        $export = $request->input('export'); // 'pdf' or 'excel'

        if ($export === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.client-pdf', ['clients' => $clients])
                ->setPaper('a4', 'landscape');
            return $pdf->download('client_report.pdf');
        }

        // 📊 Excel Export
        if ($export === 'excel') {
            return Excel::download(new ClientReportExport($request), 'client_report.xlsx');
        }

        return view('reports.client', [
            'clients' => $clients,
            'companies' => \App\Models\Company::pluck('company_name', 'address', 'id'),
            'managers' => \App\Models\Employee::selectRaw("CONCAT(fore_name, ' ', sur_name) as full_name, id")
                ->pluck('full_name', 'id'),
            'selectedCompany' => $request->company_id,
            'selectedManager' => $request->manager_id,
            'selectedStatus' => $request->status,
            'search' => $request->search,
            'contractStart' => $request->contract_start,
            'contractEnd' => $request->contract_end,
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

    public function bookingReport(Request $request)
    {
        $clientId = $request->input('client_id');
        $employeeId = $request->input('employee_id');
        $type = $request->input('type');
        $date = $request->input('shift_date');
        $export = $request->input('export'); // 'pdf' or 'excel'

        $bookings = ShiftBooking::with(['shift.shift.site.client', 'user'])
            ->when(
                $clientId,
                fn($q) =>
                $q->whereHas('shift.shift.site.client', fn($qq) => $qq->where('id', $clientId))
            )
            ->when(
                $employeeId,
                fn($q) =>
                $q->whereHas('shift', fn($qq) => $qq->where('staff_id', $employeeId))
            )
            ->when($type, fn($q) => $q->where('type', $type))
            ->when(
                $date,
                fn($q) =>
                $q->whereHas('shift', fn($qq) => $qq->whereDate('shift_date', $date))
            )
            ->latest()
            ->get();

        // 🧾 PDF Export
        if ($export === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.booking-pdf', compact('bookings'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('booking_report.pdf');
        }

        // 📊 Excel Export
        if ($export === 'excel') {
            return Excel::download(new BookingReportExport($bookings), 'booking_report_excel.xlsx');
        }

        $clients = User::role('client')->pluck('first_name', 'id');
        $employees = User::role('security_staff')->selectRaw("id, CONCAT(first_name, ' ', last_name) as full_name")
            ->pluck('full_name', 'id');

        return view('reports.booking_report', [
            'bookings' => $bookings,
            'clients' => $clients,
            'employees' => $employees,
            'selectedClient' => $clientId,
            'selectedEmployee' => $employeeId,
            'selectedType' => $type,
            'selectedDate' => $date,
        ]);
    }

    public function checkpointReport(Request $request)
    {
        $selectedSite = $request->input('site_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $exportType = $request->input('export');

        // Get list of sites for the filter dropdown
        $sites = Site::pluck('site_name', 'id');

        // Base query
        $query = PatrolCheckPoint::with('site', 'scans');

        if ($selectedSite) {
            $query->where('site_id', $selectedSite);
        }

        // Apply scan date filter if provided

        $checkpoints = $query->get();

        // Handle Export (PDF or Excel)
        if ($exportType === 'pdf') {
            $pdf = PDF::loadView('reports.pdf.checkpoints-pdf', compact('checkpoints', 'selectedSite', 'fromDate', 'toDate'));
            return $pdf->download('checkpoint_report.pdf');
        }

        if ($exportType === 'excel') {
            $data = $checkpoints->map(function ($c) {
                return [
                    'Checkpoint Name' => $c->name,
                    'Site' => $c->site->site_name ?? 'N/A', // make sure relationship is loaded
                    'Required' => $c->required ? 'Yes' : 'No',
                    'Latitude' => $c->latitude ?? 'N/A',
                    'Longitude' => $c->longitude ?? 'N/A',
                ];
            })->toArray();

            // Optional: explicitly define headings
            $headings = ['Checkpoint Name', 'Site', 'Required', 'Latitude', 'Longitude'];

            return Excel::download(new ArrayExport($data, $headings), 'checkpoint_report.xlsx');
        }

        return view('reports.checkpoint', [
            'checkpoints' => $checkpoints,
            'sites' => $sites,
            'selectedSite' => $selectedSite,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }
}
