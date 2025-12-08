<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Helpers\Logger;
use App\Models\Invoice;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use App\Models\ShiftDate;
use App\Models\EmployeeType;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Services\PayrollCalculator;
use Illuminate\Support\Facades\Auth;
use App\DataTables\PayrollsDataTable;
use App\DataTables\SubcontractorPayrollsDataTable;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index(PayrollsDataTable $dataTable)
    {
        return $dataTable->render('invoices.payrolls', [
        ]);
    }

        /**
         * Return subcontractor payrolls as JSON for client-side DataTable.
         */
        public function subcontractorData()
        {
            $invoices = Invoice::with(['subcontractor', 'site'])
                ->where('type', 'subcontractor')
                ->orderBy('id', 'desc')
                ->get();

            $rows = $invoices->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'subcontractor_name' => $inv->subcontractor ? ($inv->subcontractor->first_name . ' ' . ($inv->subcontractor->last_name ?? '')) : '',
                    'site_name' => $inv->site?->site_name ?? '',
                    'issue_date' => $inv->issue_date ? Carbon::parse($inv->issue_date)->format('d/m/Y') : '',
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d/m/Y') : '',
                    'total_shift_hours' => $inv->total_shift_hours ?? 0,
                    'net_amount' => number_format($inv->net_amount ?? 0, 2),
                    'total_amount' => number_format($inv->total_amount ?? 0, 2),
                    'status' => ($inv->paid_amount ?? 0) >= ($inv->net_amount ?? 0) ? 'Paid' : 'Unpaid',
                ];
            });

            return response()->json(['data' => $rows]);
        }

    public function store(Request $request, InvoiceService $calc)
    {
        $data = $request->validate([
            'security_staff_id' => 'required|integer|exists:users,id',
            'site_id'           => 'nullable|integer|exists:sites,id',
            'notes'             => 'nullable|string|max:355',
            'date_from'         => 'nullable|date',
            'date_to'           => 'nullable|date|after_or_equal:date_from',
            'frequency'         => 'nullable|in:weekly,fortnightly,monthly',
        ]);

        // 🟢 Handle frequency (override date range if frequency is set)
        if (!empty($data['frequency'])) {
            $today = Carbon::today();

            switch ($data['frequency']) {
                case 'weekly':
                    // last full week (Mon–Sun)
                    $startDate = $today->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->startOfDay();
                    $endDate   = $today->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                    break;

                case 'fortnightly':
                    $startDate = $today->copy()->subWeeks(2)->startOfDay();
                    $endDate   = $today->copy()->endOfDay();
                    break;

                case 'monthly':
                    $startDate = $today->copy()->subDays(30)->startOfDay();
                    $endDate   = $today->copy()->endOfDay();
                    break;
            }
        } else {
            $startDate = Carbon::parse($data['date_from'] ?? now())->startOfDay();
            $endDate   = Carbon::parse($data['date_to'] ?? now())->endOfDay();
        }

        // Ensure the provided user id belongs to a security staff user
        $user = User::find($data['security_staff_id']);
        if (! $user || ! $user->hasRole('security_staff')) {
            return response()->json(['error' => 'Selected user is not a security staff member.'], 422);
        }

        $staff = Employee::where('user_id', $user->id)->firstOrFail();

        // 🔹 Process leaves (SSP, Holiday, Unpaid) first so we can mark them and incorporate into totals
        // Use date-only comparisons for leave periods (start_date likely stored as date)
        $leaves = LeaveRequest::where('user_id', $staff->user_id)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('processed_by_payroll', false)
            ->get();

        $sspAmount = 0;
        $sspDays = 0;
        $holidayAmount = 0;
        $holidayHours = 0;
        $unpaidAmount = 0;
        $unpaidHours = 0;

        foreach ($leaves as $leave) {
            if ($leave->ssp_days) {
                $sspDays   += $leave->ssp_days;
                $sspAmount += $leave->ssp_days * 23.75;
            }

            if ($leave->holiday_days_used) {
                $holidayHours  += $leave->holiday_days_used;
                // we'll add holiday amount after invoice is generated using the payroll rate
                $holidayAmount += 0; // placeholder
            }

            if ($leave->unpaid_days) {
                $unpaidHours  += $leave->unpaid_days;
                $unpaidAmount += 0; // placeholder
            }

            $leave->processed_by_payroll = true;
            $leave->save();
        }

        // Use InvoiceService to create invoice items and invoice for the staff (this populates items)
        // Pass explicit date strings to avoid ambiguity inside the service
        $invoice = $calc->generateSecurityStaffInvoice(
            $data['security_staff_id'],
            $data['site_id'] ?? null,
            $startDate->toDateString(),
            $endDate->toDateString(),
            now()->addDays(15),
            $data['notes'] ?? null
        );

        // Now update invoice totals to include SSP/holiday/unpaid adjustments (if applicable)
        // Determine payroll rate from invoice.rate_per_hour
        $rate = $invoice->rate_per_hour ?? 0;

        // If holiday/unpaid placeholders exist, compute amounts based on rate
        // Defensive: treat incoming 'hours' values as hours; if they are days adjust accordingly
        $holidayAmount = max(0, ($holidayHours ?: 0) * $rate);
        $unpaidAmount = max(0, ($unpaidHours ?: 0) * $rate);

        // Adjust invoice amounts and save (clamp to prevent negative totals)
        $invoice->ssp_amount = max(0, $sspAmount);
        $invoice->ssp_days = max(0, $sspDays);
        $invoice->holiday_amount = $holidayAmount;
        $invoice->holiday_hours = max(0, $holidayHours);
        $invoice->unpaid_leave_amount = $unpaidAmount;
        $invoice->unpaid_leave_hours = max(0, $unpaidHours);

        // Recalculate gross/net to include adjustments, defensively clamp negatives
        $itemsSum = $invoice->items->sum('amount');
        if ($itemsSum < 0) {
            \Log::warning('Invoice items sum is negative', ['invoice_id' => $invoice->id, 'itemsSum' => $itemsSum]);
            $itemsSum = 0;
        }

        $gross = $itemsSum + $holidayAmount + max(0, $invoice->ssp_amount) - $unpaidAmount;
        if ($gross < 0) {
            \Log::warning('Computed gross payroll is negative, clamping to zero', ['invoice_id' => $invoice->id, 'gross' => $gross]);
            $gross = 0;
        }

        $invoice->gross_amount = $gross;
        $invoice->net_amount = $gross;
        $invoice->save();

        send_push_notification(
            $staff->user_id,
            'Payroll generated',
            "A new payroll has been generated for you!",
            ['invoice' => $invoice]
        );

        Logger::log(
            Auth::user(),
            'Create',
            'Payroll NO. ' . $invoice->invoice_number . ' generated for ' . $staff->first_name . ' ' . $staff->last_name
        );

        return response()->json([
            'message'   => 'Payroll created successfully',
            'payroll'   => $invoice,
            'breakdown' => [
                'ssp'     => ['amount' => $sspAmount, 'days' => $sspDays],
                'holiday' => ['amount' => $holidayAmount, 'hours' => $holidayHours],
                'unpaid'  => ['amount' => $unpaidAmount, 'hours' => $unpaidHours],
            ]
        ]);
    }


    public function update(Request $request, $id) {}

    public function edit($userId)
    {
        // Get the user (staff)
        $employee = User::findOrFail($userId);

        // Collect sites from ShiftDate records where this staff is assigned.
        // For each ShiftDate -> load shift and its site, then dedupe by site id.
        $shiftDates = ShiftDate::where('staff_id', $employee->id)
            ->with(['shift.site'])
            ->get();

        $sites = $shiftDates->map(function ($sd) {
            if (! $sd->shift || ! $sd->shift->site) return null;
            return [
                'shift_id' => $sd->shift->id,
                'site' => $sd->shift->site,
            ];
        })->filter()->unique('site.id')->values();

        return response()->json([
            'employee' => $employee,
            'sites' => $sites,
        ]);
    }
    public function show($id)
    {
        $invoice = Invoice::find($id);
        $invoice->load([
            'client',
            'subcontractor',
            'securityStaff',
            'site',
            'items',
            'items.securityStaff',
            'items.site'
        ]);

        // Recalculate totals from items if not already set
        if (!$invoice->total_shift_hours) {
            $invoice->total_shift_hours = $invoice->items->sum('hours');
            $invoice->total_break_hours = $invoice->items->sum('break_hours');
            $invoice->total_deductions_hours = $invoice->items->sum(function ($item) {
                return $item->break_hours + $item->book_on_hours + $item->book_off_hours;
            });
            $invoice->gross_amount = $invoice->items->sum('amount') + $invoice->holiday_amount + $invoice->ssp_amount - $invoice->unpaid_leave_amount;
            $invoice->net_amount = $invoice->items->sum('amount') + $invoice->holiday_amount + $invoice->ssp_amount - $invoice->unpaid_leave_amount;
        }

        $staff = User::role('security_staff')->where('id', $invoice->security_staff_id)->first();
        return view('invoices.viewpayroll', [
            'invoice' => $invoice,
            'staff' => $staff,
            'totalHours' => $invoice->items->sum(function ($item) {
                return $item->hours + $item->break_hours + $item->book_on_hours + $item->book_off_hours;
            }),
            'totalBreaks' => $invoice->items->sum('break_hours'),
            'totalBookOnHours' => $invoice->items->sum('book_on_hours'),
            'totalBookOffHours' => $invoice->items->sum('book_off_hours'),
            'sspAmount' => $invoice->ssp_amount,
            'sspDays' => $invoice->ssp_days,
            'holidayAmount' => $invoice->holiday_amount,
            'holidayHours' => $invoice->holiday_hours,
            'unpaidAmount' => $invoice->unpaid_leave_amount,
            'unpaidHours' => $invoice->unpaid_leave_hours,
            'totalShiftHours' => $invoice->total_shift_hours,
            'totalBreaks' => $invoice->total_break_hours,
            'totalBookOnHours' => $invoice->total_deductions_hours - $invoice->total_break_hours,
            'sspAmount' => $invoice->ssp_amount,
        ]);
    }

    public function delete($id)
    {
        $payroll = Invoice::findOrFail($id);
        // Defensive logging: either security staff or subcontractor
        try {
            $payeeName = $payroll->securityStaff ? ($payroll->securityStaff->first_name . ' ' . ($payroll->securityStaff->last_name ?? '')) : ($payroll->subcontractor?->name ?? 'Unknown');
        } catch (\Throwable $e) {
            $payeeName = 'Unknown';
        }
        Logger::log(Auth::user(), 'Delete', 'Payroll NO. ' . ($payroll->invoice_number ?? $payroll->id) . ' deleted for ' . $payeeName);
        $payroll->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
        ]);

        $invoices = Invoice::whereIn('id', $request->ids)->get();
        foreach ($invoices as $invoice) {
            try {
                $payeeName = $invoice->securityStaff ? ($invoice->securityStaff->first_name . ' ' . ($invoice->securityStaff->last_name ?? '')) : ($invoice->subcontractor?->name ?? 'Unknown');
            } catch (\Throwable $e) {
                $payeeName = 'Unknown';
            }
            Logger::log(Auth::user(), 'Delete', 'Payroll NO. ' . ($invoice->invoice_number ?? $invoice->id) . ' deleted for ' . $payeeName);
            $invoice->delete();
        }

        return response()->json(['message' => 'Selected payrolls deleted.']);
    }
}
