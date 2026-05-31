<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Site;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\ShiftBooking;
use Illuminate\Http\Request;
use App\Models\Subcontractor;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PatrolCheckPoint;
use App\Models\CheckCall;
use App\Models\Patrol;
use App\Services\InvoiceService;
use App\Exports\Reports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Reports\ShiftReportExport;
use App\Exports\Reports\StaffReportExport;
use App\Exports\Reports\ClientReportExport;
use App\Exports\Reports\SalaryReportExport;
use App\Exports\Reports\BookingReportExport;
use App\Exports\Reports\AvailabilityReport;
use App\Exports\Reports\PerformanceReportExport;
use App\Models\LoginActivity;

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
        $hasFilters = $request->filled('client_id')
            || $request->filled('employee_id')
            || $request->filled('subcontractor_id')
            || $request->filled('site_id')
            || $request->filled('status')
            || $request->filled('from_date')
            || $request->filled('to_date');

        // Avoid loading the entire history when page first opens.
        $shifts = collect();

        if ($hasFilters || $request->filled('export')) {
            if ($request->filled('export') && !$hasFilters) {
                return redirect()->route('reports.shift')
                    ->with('error', 'Please apply at least one filter before exporting the shift report.');
            }

            // Build a joined query to keep filtering in the database and avoid expensive whereHas/orWhereExists
            $query = ShiftDate::query()
                ->select('shift_dates.*')
                ->join('shifts', 'shifts.id', '=', 'shift_dates.shift_id')
                ->with(['shift.client', 'shift.site', 'shift.staff', 'note']);

            // Filter by client (directly on joined shifts table)
            if ($request->filled('client_id')) {
                $query->where('shifts.client_id', (int) $request->input('client_id'));
            }

            // Filter by employee (support either shift_dates.staff_id or shifts.staff_id)
            if ($request->filled('employee_id')) {
                $employeeId = (int) $request->input('employee_id');
                $query->where(function ($q) use ($employeeId) {
                    $q->where('shift_dates.staff_id', $employeeId)
                        ->orWhere('shifts.staff_id', $employeeId);
                });
            }

            // Filter by subcontractor — match the scheduling gantt's grouping exactly.
            //
            // Scheduling resolves a shift's subcontractor via findSubcontractorByStoredId():
            //   1. Subcontractor::find($stored_value)              ← tried first
            //   2. Subcontractor::where('user_id', $stored_value)  ← fallback
            // And it uses shift_dates.subcontractor_id when set, else falls back to the
            // parent shifts.subcontractor_id (NOT both — it's a COALESCE, not an OR).
            //
            // So for the selected Subcontractor row to "own" a shift on the gantt, the
            // shift's effective stored value (the COALESCE of the two columns) must
            // resolve back to that exact row under the rules above.
            if ($request->filled('subcontractor_id')) {
                $selected = Subcontractor::find((int) $request->input('subcontractor_id'));

                if (!$selected) {
                    $query->whereRaw('1 = 0');
                } else {
                    // Build the set of stored values that resolve to THIS row:
                    // - The row's own id is always a match (rule #1 hits it directly).
                    // - The row's user_id matches only if (a) no other Subcontractor row
                    //   collides on `id = user_id` (which would steal rule #1), AND
                    //   (b) this row is the lowest-id Subcontractor with that user_id
                    //   (since rule #2 returns ->first()).
                    $candidateIds = [(int) $selected->id];

                    if (!empty($selected->user_id)) {
                        $u = (int) $selected->user_id;

                        $idCollision = Subcontractor::whereKey($u)->exists();

                        if (!$idCollision) {
                            $firstByUser = Subcontractor::where('user_id', $u)
                                ->orderBy('id')
                                ->value('id');

                            if ((int) $firstByUser === (int) $selected->id) {
                                $candidateIds[] = $u;
                            }
                        }
                    }

                    $candidateIds = array_values(array_unique($candidateIds));
                    $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));

                    $query->whereRaw(
                        "COALESCE(shift_dates.subcontractor_id, shifts.subcontractor_id) IN ({$placeholders})",
                        $candidateIds
                    );

                    // Scheduling only labels a shift bar with a subcontractor when a
                    // staff is assigned (staff_id IS NOT NULL — see ShiftController
                    // formatGanttArray()). Mirror that so the report's "shifts under
                    // Subcontractor X" matches the gantt's bars labeled X 1:1.
                    $query->whereNotNull('shift_dates.staff_id');
                }
            }

            // Filter by date range (shift_date between from_date and to_date)
            if ($request->filled('from_date') && $request->filled('to_date')) {
                try {
                    $from = Carbon::parse($request->input('from_date'))->toDateString();
                    $to = Carbon::parse($request->input('to_date'))->toDateString();
                    $query->whereDate('shift_dates.shift_date', '>=', $from)
                        ->whereDate('shift_dates.shift_date', '<=', $to);
                } catch (\Exception $e) {
                    // If parsing fails, fall back to raw inputs (best-effort)
                    $query->whereDate('shift_dates.shift_date', '>=', $request->input('from_date'))
                        ->whereDate('shift_dates.shift_date', '<=', $request->input('to_date'));
                }
            }

            if ($request->filled('status')) {
                $query->whereIn('shift_dates.is_assign', (array) $request->status);
            }

            // Filter by site
            if ($request->filled('site_id')) {
                $query->where('shifts.site_id', (int) $request->input('site_id'));
            }

            $shifts = $query
                ->orderByDesc('shift_dates.shift_date')
                ->get();
        }

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

        // Subcontractors dropdown: one entry per Subcontractor row (sub_contractors table) —
        // same primary key the scheduling modal sends via /subcontractors/for-employee/{id}.
        // Label prefers company_name (what shows on the gantt's shift bars), falling back to
        // contact_person and finally the linked user's name. The filter above resolves the
        // selected Subcontractor.id to both its id AND its user_id when matching shifts.
        $subcontractors = Subcontractor::with('user:id,first_name,last_name')
            ->orderBy('company_name')
            ->get(['id', 'user_id', 'company_name', 'contact_person'])
            ->mapWithKeys(function ($s) {
                $label = trim((string) ($s->company_name ?? ''));
                if ($label === '') {
                    $label = trim((string) ($s->contact_person ?? ''));
                }
                if ($label === '' && $s->user) {
                    $label = trim(($s->user->first_name ?? '') . ' ' . ($s->user->last_name ?? ''));
                }
                return [$s->id => $label ?: ('Subcontractor #' . $s->id)];
            });

        // Sites for filter dropdown
        $sites = Site::pluck('site_name', 'id');

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
            'subcontractors' => $subcontractors,
            'sites' => $sites,
            'selectedClient' => $request->input('client_id'),
            'selectedEmployee' => $request->input('employee_id'),
            'selectedSubcontractor' => $request->input('subcontractor_id'),
            'selectedSite' => $request->input('site_id'),
            'selectedStatus' => $request->status ?? [],
            'statusOptions' => $statusOptions,
            'filterDate' => $request->input('from_date'),
            'fromDate' => $request->input('from_date'),
            'toDate' => $request->input('to_date'),
        ]);
    }

    public function bookingReport(Request $request)
    {
        $clientId = $request->input('client_id');
        $employeeId = $request->input('employee_id');
        $type = $request->input('type');
        $date = $request->input('shift_date');
        $export = $request->input('export'); // 'pdf' or 'excel'

        // Admin scoping: ShiftBooking has no admin_id column, so we scope through
        // the 'shift' (ShiftDate) relationship which has BelongsToAdmin applied.
        // superadmin sees all; admin sees their own; others see admin_id IS NULL records.
        $bookings = ShiftBooking::with(['shift.shift.site.client', 'user'])
            ->when(
                !auth()->user()->hasRole('superadmin'),
                fn($q) => $q->whereHas('shift')  // BelongsToAdmin scope on ShiftDate auto-filters
            )
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
        // PatrolCheckPoint has no admin_id; scope through 'site' (Site has BelongsToAdmin).
        // superadmin sees all; admin sees their sites' checkpoints; others see unowned sites.
        $query = PatrolCheckPoint::with('site')
            ->when(
                !auth()->user()->hasRole('superadmin'),
                fn($q) => $q->whereHas('site')  // BelongsToAdmin scope on Site auto-filters
            );

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

    public function performanceReport(Request $request)
    {
    $from = $request->input('from_date');
    $to = $request->input('to_date');
    $clientId = $request->input('client_id');
    $siteId = $request->input('site_id');
    $staffId = $request->input('staff_id');

        // Build with-array defensively: always include required relations
        $with = ['shift', 'shift.client', 'shift.site', 'shift.staff'];

        // Detect optional relation names on ShiftDate model to avoid runtime errors
        if (method_exists(ShiftDate::class, 'checkcalls')) $with[] = 'checkcalls';
        if (method_exists(ShiftDate::class, 'patrols')) $with[] = 'patrols';
        if (method_exists(ShiftDate::class, 'checkpoints')) $with[] = 'checkpoints';
        if (method_exists(ShiftDate::class, 'patrolCheckpoints')) $with[] = 'patrolCheckpoints';

        $query = ShiftDate::query()->with($with)
            ->when($from, fn($q) => $q->whereDate('shift_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('shift_date', '<=', $to))
            ->when($clientId, function ($q) use ($clientId) {
                $q->whereHas('shift.client', fn($q2) => $q2->where('id', $clientId));
            })
            ->when($siteId, function ($q) use ($siteId) {
                $q->whereHas('shift.site', fn($q2) => $q2->where('id', $siteId));
            })
            ->when($staffId, function ($q) use ($staffId) {
                // apply staff filter either from ShiftDate.staff_id or related shift.staff_id
                $q->where(function ($q2) use ($staffId) {
                    $q2->where('staff_id', $staffId)
                        ->orWhereHas('shift', fn($qq) => $qq->where('staff_id', $staffId));
                });
            });

        $shiftDates = $query->get();

        // Per-staff stats: only completed/missed counts for checkcalls and patrols.
        $isMissed = function ($item) {
            if (!$item) return false;
            if (isset($item->is_missed)) return (bool)$item->is_missed;
            if (isset($item->missed)) return (bool)$item->missed;
            if (isset($item->status) && is_string($item->status)) {
                $s = strtolower($item->status);
                return in_array($s, ['missed', 'failed', 'no-response', 'not-checked']);
            }
            if (isset($item->result) && is_string($item->result)) {
                $r = strtolower($item->result);
                return in_array($r, ['missed', 'failed']);
            }
            return false;
        };

        $checkcallRelation = method_exists(ShiftDate::class, 'checkcalls') ? 'checkcalls' : (method_exists(ShiftDate::class, 'check_call_attempts') ? 'check_call_attempts' : null);
        $patrolRelation = method_exists(ShiftDate::class, 'patrols') ? 'patrols' : (method_exists(ShiftDate::class, 'checkpoints') ? 'checkpoints' : (method_exists(ShiftDate::class, 'patrolCheckpoints') ? 'patrolCheckpoints' : null));

        // Group by staff_id directly on ShiftDate (staff relation lives on ShiftDate).
        $stats = $shiftDates
            ->groupBy(fn($sd) => $sd->staff_id ?? 'unassigned')
            ->map(function ($group, $staffId) use ($checkcallRelation, $patrolRelation, $isMissed) {
                $first = $group->first();

                $staffName = ($first && isset($first->staff) && $first->staff)
                    ? trim(($first->staff->first_name ?? '') . ' ' . ($first->staff->last_name ?? ''))
                    : 'Unassigned';

                $completedCheckcalls = 0;
                $missedCheckcalls = 0;
                $completedPatrols = 0;
                $missedPatrols = 0;

                if ($checkcallRelation) {
                    foreach ($group as $sd) {
                        $items = $sd->{$checkcallRelation} ?? collect();
                        foreach ($items as $it) {
                            if ($isMissed($it)) $missedCheckcalls++; else $completedCheckcalls++;
                        }
                    }
                }

                if ($patrolRelation) {
                    foreach ($group as $sd) {
                        $items = $sd->{$patrolRelation} ?? collect();
                        foreach ($items as $it) {
                            if ($isMissed($it)) $missedPatrols++; else $completedPatrols++;
                        }
                    }
                }

                return [
                    'staff_id' => $staffId,
                    'staff_name' => $staffName,
                    'completed_checkcalls' => $completedCheckcalls,
                    'missed_checkcalls' => $missedCheckcalls,
                    'completed_patrols' => $completedPatrols,
                    'missed_patrols' => $missedPatrols,
                ];
            });

        // Remove any unassigned rows from the per-staff stats (we don't show unassigned shifts)
        $stats = $stats->reject(fn($row) => ($row['staff_id'] === 'unassigned'))->values();

        // Dropdowns
        $clients = User::role('client')->pluck('name', 'id');
        $sites = collect();
        if ($clientId) $sites = Site::where('client_id', $clientId)->pluck('site_name', 'id');
        $staffs = User::role('security_staff')->orderBy('first_name')->get();

        // ---------- Export handling ----------
        if ($request->filled('export')) {
            $exportType = $request->input('export');
            $fileNameBase = 'Performance_Report_' . now()->format('Y_m_d_His');

            $statsArray = $stats->map(fn($r) => [
                'staff_id' => $r['staff_id'],
                'staff_name' => $r['staff_name'],
                'completed_checkcalls' => $r['completed_checkcalls'],
                'missed_checkcalls' => $r['missed_checkcalls'],
                'completed_patrols' => $r['completed_patrols'],
                'missed_patrols' => $r['missed_patrols'],
            ])->toArray();

            if ($exportType === 'pdf') {
                $pdf = PDF::loadView('reports.pdf.performance-pdf', [
                    'stats' => $statsArray,
                    'filters' => ['from' => $from, 'to' => $to, 'client' => $clientId, 'site' => $siteId, 'staff' => $staffId],
                ]);
                return $pdf->download($fileNameBase . '.pdf');
            }

            if ($exportType === 'excel') {
                return Excel::download(new PerformanceReportExport([
                    'stats' => $statsArray,
                ]), $fileNameBase . '.xlsx');
            }

            // CSV fallback
            $csvName = $fileNameBase . '.csv';
            $callback = function () use ($statsArray) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Staff', 'Completed Checkcalls', 'Missed Checkcalls', 'Completed Patrols', 'Missed Patrols']);
                foreach ($statsArray as $row) {
                    fputcsv($out, [
                        $row['staff_name'],
                        $row['completed_checkcalls'],
                        $row['missed_checkcalls'],
                        $row['completed_patrols'],
                        $row['missed_patrols'],
                    ]);
                }
                fclose($out);
            };

            return response()->stream($callback, 200, [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename={$csvName}",
            ]);
        }

        // Default: return view with data
        return view('reports.performance', [
            'stats' => $stats,
            'clients' => $clients,
            'sites' => $sites,
            'staffs' => $staffs,
            'selectedClient' => $clientId,
            'selectedSite' => $siteId,
            'selectedStaff' => $staffId,
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function loginReport(Request $request)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $search = $request->input('search');
        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();

        // Roles to exclude from this report
        $excludeRoles = ['security_staff', 'client', 'subcontractor'];

        // Users for the dropdown: exclude the roles above
        $usersQuery = User::whereDoesntHave('roles', function ($r) use ($excludeRoles) {
            $r->whereIn('name', $excludeRoles);
        });

        if ($authUser && $authUser->hasRole('admin')) {
            $usersQuery->where('admin_id', $authUser->id);
        }

        $users = $usersQuery->orderBy('first_name')->get();

        // Only show results when at least one filter is applied
        $hasFilters = $request->filled('start_date') || $request->filled('end_date') || $request->filled('search');

        // Prevent exporting without filters
        if ($request->filled('export') && !$hasFilters) {
            return redirect()->route('reports.logins')
                ->with('error', 'Please apply at least one filter before exporting the login activity report.');
        }

        $activities = collect();

        if ($hasFilters) {
            $query = LoginActivity::with('user');

            if ($authUser && $authUser->hasRole('admin')) {
                $query->where('admin_id', $authUser->id);
            }

            // Exclude users with specific roles from this report
            $query->whereHas('user', function ($u) use ($excludeRoles, $authUser) {
                $u->whereDoesntHave('roles', function ($r) use ($excludeRoles) {
                    $r->whereIn('name', $excludeRoles);
                });

                if ($authUser && $authUser->hasRole('admin')) {
                    $u->where('admin_id', $authUser->id);
                }
            });

            if ($start) {
                $query->whereDate('login_at', '>=', $start);
            }

            if ($end) {
                $query->whereDate('login_at', '<=', $end);
            }

            if ($search) {
                if (is_numeric($search)) {
                    $query->whereHas('user', fn($q) => $q->where('id', (int) $search));
                } else {
                    $like = "%{$search}%";
                    $query->whereHas('user', function ($q) use ($like) {
                        $q->where('first_name', 'like', $like)
                          ->orWhere('last_name', 'like', $like)
                          ->orWhere('email', 'like', $like);
                    });
                }
            }

            $activities = $query->orderByDesc('login_at')->get();
        }

        // Handle exports when filters applied
        if ($request->filled('export') && $hasFilters) {
            $export = $request->input('export');
            $fileName = 'Login_Activities_' . now()->format('Y_m_d_His');

            if ($export === 'pdf') {
                $pdf = Pdf::loadView('reports.pdf.logins-pdf', ['activities' => $activities]);
                return $pdf->download($fileName . '.pdf');
            }

            if ($export === 'excel') {
                $data = $activities->map(function ($a) {
                    return [
                        'User' => trim((optional($a->user)->first_name ?? '') . ' ' . (optional($a->user)->last_name ?? '')),
                        'Email' => optional($a->user)->email,
                        'Login At' => $a->login_at ? $a->login_at->toDateTimeString() : '',
                        'Logout At' => $a->logout_at ? $a->logout_at->toDateTimeString() : '',
                        'Duration Minutes' => ($a->login_at && $a->logout_at) ? (int) $a->login_at->diffInMinutes($a->logout_at) : '',
                        'IP' => $a->ip_address,
                    ];
                })->toArray();

                $headings = ['User', 'Email', 'Login At', 'Logout At', 'Duration Minutes', 'IP'];
                return Excel::download(new ArrayExport($data, $headings), $fileName . '.xlsx');
            }
        }

        return view('reports.logins', [
            'activities' => $activities,
            'users' => $users,
            'startDate' => $start,
            'endDate' => $end,
            'search' => $search,
            'hasFilters' => $hasFilters,
        ]);
    }

    public function salaryReport(Request $request)
    {
        $staffId = $request->input('staff_id');
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $siteId = $request->input('site_id');

        // Staff dropdown (we will use select2 AJAX on the view, but provide small list optionally)
        $staffOptions = User::role('security_staff')->orderBy('first_name')->get();

        // Build invoice query: only security_staff invoices
        $query = Invoice::query()
            ->with(['site', 'employee', 'securityStaff'])
            ->where('type', 'security_staff');

        if ($staffId) {
            $query->where('security_staff_id', $staffId);
        }

        // filter by issue_date range (use issue_date as primary)
        if ($from) {
            $query->whereDate('issue_date', '>=', Carbon::parse($from)->toDateString());
        }
        if ($to) {
            $query->whereDate('issue_date', '<=', Carbon::parse($to)->toDateString());
        }

        // optional site filter
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        // get invoices (all matching - small result sets expected). If large sets are possible, switch to pagination or streaming for exports.
        $invoices = $query->orderByDesc('issue_date')->get();

        // aggregate totals
        $totalCount = $invoices->count();
        $totalGross = $invoices->sum(fn($i) => (float) ($i->gross_amount ?? 0));
        $totalNet = $invoices->sum(fn($i) => (float) ($i->net_amount ?? 0));
        // prefer total_shift_hours, then total_duration_hours, then 0
        $totalHours = $invoices->sum(function ($i) {
            if (!empty($i->total_shift_hours)) return (float)$i->total_shift_hours;
            if (!empty($i->total_duration_hours)) return (float)$i->total_duration_hours;
            return 0;
        });

        // prepare payload for exports
        $payload = [
            'invoices' => $invoices,
            'totals' => [
                'count' => $totalCount,
                'gross' => $totalGross,
                'net' => $totalNet,
                'hours' => $totalHours,
            ],
            'filters' => [
                'staff_id' => $staffId,
                'from' => $from,
                'to' => $to,
                'site_id' => $siteId,
            ],
        ];

        // Exports
        if ($request->filled('export') && $staffId) {
            $exportType = $request->input('export');
            $fileBase = 'Salary_payrolls_' . now()->format('Y_m_d_His');

            if ($exportType === 'pdf') {
                // generate pdf
                $pdf = PDF::loadView('reports.pdf.salary-pdf', array_merge($payload, [
                    'staff' => User::find($staffId),
                ]));
                return $pdf->download($fileBase . '.pdf');
            }

            if ($exportType === 'excel') {
                return Excel::download(new SalaryReportExport($payload), $fileBase . '.xlsx');
            }

            // csv fallback
            $csvName = $fileBase . '.csv';
            $callback = function () use ($invoices, $payload) {
                $out = fopen('php://output', 'w');
                // headers
                fputcsv($out, ['Invoice #', 'Issue Date', 'Period From', 'Period To', 'Site', 'Worked Hours', 'Gross', 'Net']);
                foreach ($invoices as $inv) {
                    fputcsv($out, [
                        $inv->invoice_number,
                        optional($inv->issue_date)->toDateString() ?? $inv->issue_date,
                        optional($inv->date_from)->toDateString() ?? $inv->date_from,
                        optional($inv->date_to)->toDateString() ?? $inv->date_to,
                        $inv->site?->site_name ?? '',
                        $inv->total_shift_hours ?? $inv->total_duration_hours ?? 0,
                        $inv->gross_amount ?? 0,
                        $inv->net_amount ?? 0,
                    ]);
                }
                // totals row
                fputcsv($out, []);
                fputcsv($out, ['Totals', '', '', '', '', $payload['totals']['hours'], $payload['totals']['gross'], $payload['totals']['net']]);
                fclose($out);
            };

            return response()->stream($callback, 200, [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename={$csvName}",
            ]);
        }

        // sites for client dropdown if client filter used earlier; keep simple empty collection
        $sites = collect();
        return view('reports.salary', [
            'staffOptions' => $staffOptions,
            'invoices' => $invoices,
            'totals' => $payload['totals'],
            'selectedStaff' => $staffId,
            'fromDate' => $from,
            'toDate' => $to,
            'sites' => $sites,
            'filters' => $payload['filters'],
        ]);
    }

    public function availabilityReport(Request $request)
    {
        $clientId = $request->input('client_id');
        $employeeId = $request->input('employee_id');
        $startDate = $request->input('start_date');
        $startTime = $request->input('start_time');
        $endDate = $request->input('end_date');
        $endTime = $request->input('end_time');
        $days = $request->input('days', []);
        $export = $request->input('export'); // 'pdf' or 'excel'

        $availabilities = collect();

        // Prefer explicit weekday selection (`days[]`) if provided
        // Availability has no admin_id; scope through 'user' (User has BelongsToAdmin).
        $isSuperAdmin = auth()->user()->hasRole('superadmin');

        if (!empty($days)) {
            // sanitize to integers 0..6
            $days = array_values(array_filter(array_map('intval', (array)$days), function ($v) {
                return $v >= 0 && $v <= 6;
            }));

            $query = \App\Models\Availability::with('user')
                ->when(!$isSuperAdmin, fn($q) => $q->whereHas('user'))  // BelongsToAdmin on User auto-filters
                ->when($employeeId, fn($q) => $q->whereHas('user', fn($qq) => $qq->where('id', $employeeId)))
                ->when($clientId, fn($q) => $q->whereHas('user', fn($qq) => $qq->where('client_id', $clientId)))
                ->whereIn('day_of_week', $days);

            // If time range provided, filter by overlapping times
            if ($startTime && $endTime) {
                $reqStart = Carbon::parse($startTime)->format('H:i:s');
                $reqEnd = Carbon::parse($endTime)->format('H:i:s');
                $query->whereTime('start_time', '<=', $reqEnd)->whereTime('end_time', '>=', $reqStart);
            }

            $availabilities = $query->get();

        } elseif ($startDate && $endDate) {
            // Compute weekdays covered by the date range and filter availabilities.
            try {
                $period = new \DatePeriod(new \DateTime($startDate), new \DateInterval('P1D'), (new \DateTime($endDate))->modify('+1 day'));
                $daysInRange = [];
                foreach ($period as $d) {
                    $daysInRange[] = (int) $d->format('w');
                }
                $daysInRange = array_values(array_unique($daysInRange));

                $query = \App\Models\Availability::with('user')
                    ->when(!$isSuperAdmin, fn($q) => $q->whereHas('user'))  // BelongsToAdmin on User auto-filters
                    ->when($employeeId, fn($q) => $q->whereHas('user', fn($qq) => $qq->where('id', $employeeId)))
                    ->when($clientId, fn($q) => $q->whereHas('user', fn($qq) => $qq->where('client_id', $clientId)))
                    ->whereIn('day_of_week', $daysInRange);

                // If time range provided, filter by overlapping times
                if ($startTime && $endTime) {
                    $reqStart = Carbon::parse($startTime)->format('H:i:s');
                    $reqEnd = Carbon::parse($endTime)->format('H:i:s');
                    $query->whereTime('start_time', '<=', $reqEnd)->whereTime('end_time', '>=', $reqStart);
                }

                $availabilities = $query->get();
            } catch (\Exception $e) {
                $availabilities = collect();
            }
        }

        // If no date/day filters provided but a single employee was selected,
        // return all availability rows for that employee.
        if ($availabilities->isEmpty() && $employeeId && empty($days) && empty($startDate) && empty($endDate)) {
            $query = \App\Models\Availability::with('user')
                ->when(!$isSuperAdmin, fn($q) => $q->whereHas('user'))  // BelongsToAdmin on User auto-filters
                ->when($employeeId, fn($q) => $q->whereHas('user', fn($qq) => $qq->where('id', $employeeId)))
                ->when($clientId, fn($q) => $q->whereHas('user', fn($qq) => $qq->where('client_id', $clientId)));

            $availabilities = $query->get();
        }

        // Exports (Availability export class exists and expects collection)
        if ($export === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.availability-pdf', compact('availabilities'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('availability_report.pdf');
        }

        if ($export === 'excel') {
            return Excel::download(new AvailabilityReport($availabilities), 'availability_report.xlsx');
        }

        $clients = User::role('client')->pluck('first_name', 'id');
        $employees = User::role('security_staff')->selectRaw("id, CONCAT(first_name, ' ', last_name) as full_name")
            ->pluck('full_name', 'id');

        return view('reports.availability', [
            'bookings' => $availabilities,
            'clients' => $clients,
            'employees' => $employees,
            'selectedClient' => $clientId,
            'selectedEmployee' => $employeeId,
            'startDate' => $startDate,
            'startTime' => $startTime,
            'endDate' => $endDate,
            'endTime' => $endTime,
        ]);
    }

    /**
     * Check Calls & Patrols report.
     *
     * Shows ONLY check calls and patrols that are completed AND awaiting
     * approval (status = completed, approval_status = pending/null). Admins can
     * approve/reject them inline (reusing the existing approve/reject endpoints),
     * see uploaded media, and jump straight to the parent shift. Data only loads
     * once at least one filter (client, site, staff, date range) is applied.
     */
    public function checkCallsPatrolsReport(Request $request)
    {
        $clientId = $request->input('client_id');
        $siteId   = $request->input('site_id');
        $staffId  = $request->input('staff_id');
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        // If the user entered the range backwards (from after to), swap them so
        // the filter still returns the intended window instead of nothing.
        if ($fromDate && $toDate && $fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $hasFilters = $request->filled('client_id')
            || $request->filled('site_id')
            || $request->filled('staff_id')
            || $request->filled('from_date')
            || $request->filled('to_date');

        $checkcalls = collect();
        $patrols    = collect();

        if ($hasFilters) {
            // Completed + pending-approval (treat null approval_status as pending).
            $pendingApproval = function ($q) {
                $q->whereNull('approval_status')->orWhere('approval_status', 'pending');
            };

            // ---------------- Check Calls ----------------
            // CheckCall -> shiftDate (via shift_id) -> shift -> site/client
            $checkcalls = CheckCall::query()
                ->with([
                    'shiftDate.shift.site',
                    'shiftDate.shift.client',
                    'shiftDate.staff',
                    'employee',
                    'media',
                ])
                ->where('status', 'completed')
                ->where($pendingApproval)
                ->when($fromDate, fn ($q) => $q->whereDate('scheduled_time', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('scheduled_time', '<=', $toDate))
                ->when($staffId, function ($q) use ($staffId) {
                    $q->where(function ($q2) use ($staffId) {
                        $q2->where('employee_id', $staffId)
                            ->orWhereHas('shiftDate', fn ($qq) => $qq->where('staff_id', $staffId));
                    });
                })
                ->when($siteId, fn ($q) => $q->whereHas('shiftDate.shift', fn ($qq) => $qq->where('site_id', $siteId)))
                ->when($clientId, fn ($q) => $q->whereHas('shiftDate.shift', fn ($qq) => $qq->where('client_id', $clientId)))
                ->orderByDesc('scheduled_time')
                ->get();

            // ---------------- Patrols ----------------
            // Patrol -> shift (ShiftDate via shift_id) -> shift -> site/client
            $patrols = Patrol::query()
                ->with([
                    'shift.shift.site',
                    'shift.shift.client',
                    'shift.staff',
                    'media',
                ])
                ->where('status', 'completed')
                ->where($pendingApproval)
                ->when($fromDate, fn ($q) => $q->whereDate('start_time', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('start_time', '<=', $toDate))
                ->when($staffId, fn ($q) => $q->whereHas('shift', fn ($qq) => $qq->where('staff_id', $staffId)))
                ->when($siteId, fn ($q) => $q->whereHas('shift.shift', fn ($qq) => $qq->where('site_id', $siteId)))
                ->when($clientId, fn ($q) => $q->whereHas('shift.shift', fn ($qq) => $qq->where('client_id', $clientId)))
                ->orderByDesc('start_time')
                ->get();
        }

        // Filter dropdown data (matches conventions used by other reports).
        $clients = User::role('client')->pluck('name', 'id');
        $sites   = Site::pluck('site_name', 'id');
        $staffs  = User::role('security_staff')->orderBy('first_name')->get();

        return view('reports.checkcalls-patrols', [
            'checkcalls'     => $checkcalls,
            'patrols'        => $patrols,
            'clients'        => $clients,
            'sites'          => $sites,
            'staffs'         => $staffs,
            'selectedClient' => $clientId,
            'selectedSite'   => $siteId,
            'selectedStaff'  => $staffId,
            'fromDate'       => $fromDate,
            'toDate'         => $toDate,
            'hasFilters'     => $hasFilters,
        ]);
    }
}
