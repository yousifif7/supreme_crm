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
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use App\Services\PayrollCalculator;
use App\DataTables\PayrollsDataTable;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index(PayrollsDataTable $dataTable)
    {
        return $dataTable->render('invoices.payrolls');
    }

    public function store(Request $request, InvoiceService $calc)
    {
        $data = $request->validate([
            'security_staff_id' => 'required|integer|exists:users,id',
            'site_id'           => 'nullable|integer|exists:sites,id',
            'notes'             => 'nullable|string|max:355',
            'date_from'         => 'nullable|date',
            'date_to'           => 'nullable|date|after_or_equal:date_from',
        ]);

        $staff = Employee::where('user_id', $data['security_staff_id'])->firstOrFail();

        $startDate = Carbon::parse($data['date_from'] ?? now());
        $endDate   = Carbon::parse($data['date_to'] ?? now());

        // Base payroll calculations
        $payroll = $calc->calculatePayroll($staff, $data['site_id'] ?? null, $startDate, $endDate);

        // SSP & Holiday based on leave requests
        $leaves = LeaveRequest::where('user_id', $staff->user_id)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$startDate, $endDate])
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
                $sspDays += $leave->ssp_days;
                $sspAmount += $leave->ssp_days * 23.75; // SSP rate
            }

            if ($leave->holiday_days_used) {
                $holidayHours += $leave->holiday_days_used;
                $holidayAmount += $leave->holiday_days_used * $payroll['rate'];
            }

            if ($leave->unpaid_days) {
                $unpaidHours += $leave->unpaid_days;
                $unpaidAmount += $leave->unpaid_days * $payroll['rate'];
            }

            $leave->processed_by_payroll = true;
            $leave->save();
        }

        $invoice = Invoice::create([
            'security_staff_id'      => $staff->user_id,
            'site_id'                => $data['site_id'] ?? null,
            'notes'                  => $data['notes'] ?? null,
            'issue_date'             => now(),
            'due_date'               => now()->addDays(15),
            'date_from'              => $startDate,
            'date_to'                => $endDate,
            'rate_per_hour'          => $payroll['rate'],
            'total_shift_hours'      => $payroll['total_hours'] - $payroll['total_book_on_hours'] - $payroll['total_book_off_hours'],
            'total_duration_hours'   => $payroll['total_hours'],
            'total_break_hours'      => $payroll['total_breaks'],
            'total_deductions_hours' => $payroll['total_book_on_hours'] + $payroll['total_book_off_hours'],
            'gross_amount'           => $payroll['gross_amount'] + $holidayAmount + $sspAmount - $unpaidAmount,
            'net_amount'             => $payroll['net_amount'] + $holidayAmount + $sspAmount - $unpaidAmount,
            'ssp_amount'             => $sspAmount,
            'ssp_days'               => $sspDays,
            'holiday_amount'         => $holidayAmount,
            'holiday_hours'          => $holidayHours,
            'unpaid_leave_amount'    => $unpaidAmount,
            'unpaid_leave_hours'     => $unpaidHours,
            'type'                   => 'security_staff',
            'invoice_number'         => Invoice::generateInvoiceNumber('security_staff'),
        ]);

        send_push_notification(
            $staff->user_id,
            'Payroll generated',
            "A new payroll has been generated for you!",
            ['leave' => $leave]
        );

        return response()->json([
            'message' => 'Payroll created successfully',
            'payroll' => $invoice,
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

        return view('invoices.viewpayroll', [
            'invoice' => $invoice,
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
