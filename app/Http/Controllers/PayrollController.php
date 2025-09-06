<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use App\Models\ShiftDate;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use App\DataTables\PayrollsDataTable;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index(PayrollsDataTable $dataTable)
    {
        return $dataTable->render('invoices.payrolls');
    }
    public function store(Request $request)
{
    $data = $request->validate([
        'security_staff_id' => 'required|integer|exists:users,id',
        'site_id'           => 'nullable|integer|exists:sites,id',
        'notes'             => 'nullable|string|max:355',
        'date_from'         => 'nullable|date',
        'date_to'           => 'nullable|date|after_or_equal:date_from',
    ]);

    // Get Employee record
    $staff = Employee::where('user_id', $data['security_staff_id'])->firstOrFail();

    // Payroll period
    $startDate = isset($data['date_from']) ? Carbon::parse($data['date_from'])->startOfDay() : now()->startOfMonth();
    $endDate   = isset($data['date_to']) ? Carbon::parse($data['date_to'])->endOfDay() : now()->endOfMonth();

    // Fetch all relevant shifts
    $shifts = Shift::where('staff_id', $staff->user_id)
        ->when($data['site_id'], fn($q) => $q->where('site_id', $data['site_id']))
        ->get();

    $totalHours = 0;
    $totalBreaks = 0;
    $totalBookOnHours = 0;
    $totalBookOffHours = 0;

    foreach ($shifts as $shift) {
        // Allowed shift days
        $daysAllowed = [];
        if ($shift->days) {
            $shiftDays = json_decode($shift->days, true);
            foreach ($shiftDays as $dayGroup) {
                $daysAllowed = array_merge($daysAllowed, explode(',', $dayGroup));
            }
        }

        // Fetch shift dates within payroll period
        $shiftDates = ShiftDate::where('shift_id', $shift->id)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->orderBy('id')
            ->get();

        foreach ($shiftDates as $shiftDate) {
            $date = Carbon::parse($shiftDate->shift_date);
            if (!in_array($date->format('D'), $daysAllowed)) continue;

            $startTime = $shiftDate->start_time;
            $endTime = $shiftDate->end_time;
            $breakMinutes = $shift->{'break-mins_shift'} ?? 0;

            $startDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
            $endDateTime   = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);

            if ($endDateTime->lessThan($startDateTime)) $endDateTime->addDay();

            $durationMinutes = $startDateTime->diffInMinutes($endDateTime);
            $totalHours += ($durationMinutes - $breakMinutes) / 60;
            $totalBreaks += $breakMinutes / 60;

            // Absentee calculations
            if ($shiftDate->absentee_start_time) {
                $absStart = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->absentee_start_time);
                if ($absStart->between($startDateTime, $endDateTime)) {
                    $totalBookOnHours += $startDateTime->diffInMinutes($absStart) / 60;
                }
            }

            if ($shiftDate->absentee_end_time) {
                $absEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftDate->absentee_end_time);
                if ($absEnd->between($startDateTime, $endDateTime)) {
                    $totalBookOffHours += $absEnd->diffInMinutes($endDateTime) / 60;
                }
            }
        }
    }

    // Payroll calculations
    $rate = $staff->guard_rate ?? 0;
    $totalDeductions = ($totalBookOnHours + $totalBookOffHours) * $rate;
    $grossAmount = $totalHours * $rate;
    $netAmount = $grossAmount - $totalDeductions;

    // Create payroll
    $invoiceNumber = Invoice::generateInvoiceNumber('security_staff');

    $payroll = Invoice::create([
        'security_staff_id'      => $staff->user_id,
        'site_id'                => $data['site_id'] ?? null,
        'notes'                  => $data['notes'] ?? null,
        'issue_date'             => now(),
        'due_date'               => now()->addDays(15),
        'date_from'              => $startDate,
        'date_to'                => $endDate,
        'rate_per_hour'          => $rate,
        'total_shift_hours'      => $totalHours - $totalBookOnHours - $totalBookOffHours,
        'total_duration_hours'   => $totalHours,
        'total_break_hours'      => $totalBreaks,
        'total_deductions_hours' => $totalBookOnHours + $totalBookOffHours,
        'gross_amount'           => $grossAmount,
        'net_amount'             => $netAmount,
        'type'                   => 'security_staff',
        'invoice_number'         => $invoiceNumber,
    ]);

    return response()->json([
        'message' => 'Payroll created successfully',
        'payroll' => $payroll
    ]);
}

    public function update(Request $request, $id) {}

    public function edit($userId)
    {
        // Get the user (staff)
        $employee = User::findOrFail($userId);

        // Get all shifts this staff has worked
        $sites = Shift::where('staff_id', $employee->id) // assuming 'staff_id' in shifts references 'users.id'
            ->with('site:id,site_name')
            ->get()
            ->map(function ($shift) {
                return [
                    'shift_id' => $shift->id,
                    'site' => $shift->site,
                ];
            })
            ->unique('site.id') // avoid duplicates if multiple shifts in same site
            ->values();

        return response()->json([
            'employee' => $employee,
            'sites' => $sites,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'client',
            'subcontractor',
            'securityStaff',
            'site',
            'items',
            'items.securityStaff',
            'items.site'
        ]);

        // Calculate totals from items if not already set
        if (!$invoice->total_shift_hours) {
            $invoice->total_shift_hours = $invoice->items->sum('hours');
            $invoice->total_break_hours = $invoice->items->sum('break_hours');
            $invoice->total_deductions_hours = $invoice->items->sum(function ($item) {
                return $item->break_hours + $item->book_on_hours + $item->book_off_hours;
            });
            $invoice->gross_amount = $invoice->items->sum('amount');
            $invoice->net_amount = $invoice->items->sum('amount'); // Adjust for deductions if needed
        }

        return view('invoices.viewpayroll', [
            'invoice' => $invoice,
            'totalHours' => $invoice->items->sum(function ($item) {
                return $item->hours + $item->break_hours + $item->book_on_hours + $item->book_off_hours;
            }),
            'totalBreaks' => $invoice->items->sum('break_hours'),
            'totalBookOnHours' => $invoice->items->sum('book_on_hours'),
            'totalBookOffHours' => $invoice->items->sum('book_off_hours'),
        ]);
    }

    public function delete($id)
    {
        $payroll = Invoice::findOrFail($id);
        $payroll->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pay_rolls,id',
        ]);

        Invoice::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected payrolls deleted.']);
    }
}
