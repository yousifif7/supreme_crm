<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Shift;
use App\Models\Client;
use App\Models\Invoice;
use Carbon\CarbonPeriod;
use App\Models\ShiftDate;
use App\Models\EmployeeType;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\DataTables\InvoicesDataTable;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\GenerateInvoiceRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class InvoiceController extends Controller
{

    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function generateClientInvoice(GenerateInvoiceRequest $request)
    {
        $newStart = Carbon::parse($request->date_from);
        $newEnd   = Carbon::parse($request->date_to);

        // Overlap check
        $overlap = Invoice::where(function ($query) use ($newStart, $newEnd) {
            $query->whereBetween('date_from', [$newStart, $newEnd])
                ->orWhereBetween('date_to', [$newStart, $newEnd])
                ->orWhere(function ($query) use ($newStart, $newEnd) {
                    $query->where('date_from', '<=', $newStart)
                        ->where('date_to', '>=', $newEnd);
                });
        })
            ->where('client_id', $request->client_id)
            ->where('site_id', $request->site_id)
            ->exists();

        if ($overlap) {
            return response()->json([
                'errors' => ['date_from' => ['An invoice already exists for this date range.']]
            ], 422);
        }

        try {
            $invoice = $this->invoiceService->generateClientInvoice(
                $request->client_id,
                $request->site_id,
                $request->date_from,
                $request->date_to,
                $request->due_date,
                $request->notes
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => ['shift' => ['No shifts were found for the selected date range.']]
            ], 422);
        }

        // ----------------------------
        // Additional Payroll Calculations
        // ----------------------------
        $employeeLeaves = LeaveRequest::where('user_id', $request->employee_id ?? null)
            ->where('start_date', '>=', $request->date_from)
            ->where('end_date', '<=', $request->date_to)
            ->where('processed_by_payroll', false)
            ->get();

        $totalHours = 0;
        $totalSSP = 0;
        $totalHolidayPay = 0;
        $totalUnpaidLeave = 0;

        foreach ($employeeLeaves as $leave) {
            $totalHours += $leave->hours;

            if ($leave->type === 'sick_leave') {
                $totalSSP += ($leave->ssp_days ?? 0) * 23.75;
            }

            if ($leave->type === 'annual_leave') {
                $totalHolidayPay += ($leave->holiday_days_used ?? 0) * ($request->hourly_rate ?? 10);
                $totalUnpaidLeave += ($leave->unpaid_days ?? 0) * ($request->hourly_rate ?? 10);
            }

            if ($leave->type === 'emergency') {
                // mark paid/unpaid based on leave.paid
                $totalHolidayPay += ($leave->paid ? $leave->hours * ($request->hourly_rate ?? 10) : 0);
                $totalUnpaidLeave += (!$leave->paid ? $leave->hours * ($request->hourly_rate ?? 10) : 0);
            }

            // Mark leave processed
            $leave->processed_by_payroll = true;
            $leave->save();
        }

        $invoice->total_hours = $totalHours;
        $invoice->total_sick_pay = $totalSSP;
        $invoice->total_holiday_pay = $totalHolidayPay;
        $invoice->total_unpaid_leave = $totalUnpaidLeave;
        $invoice->processed_by_payroll = true;
        $invoice->save();

        return response()->json([
            'message' => 'Client invoice generated successfully',
            'invoice' => $invoice->load('items'),
        ]);
    }

    public function generateSubcontractorInvoice(GenerateInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->generateSubcontractorInvoice(
            $request->client_id,
            $request->date_from,
            $request->date_to,
            $request->due_date,
            $request->notes
        );

        return response()->json([
            'message' => 'Subcontractor invoice generated successfully',
            'invoice' => $invoice->load('items'),
        ]);
    }

    public function generateSecurityStaffInvoice(GenerateInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->generateSecurityStaffInvoice(
            $request->employee_id,
            $request->site_id,
            $request->date_from,
            $request->date_to,
            $request->due_date,
            $request->notes
        );

        return response()->json([
            'message' => 'Security staff invoice generated successfully',
            'invoice' => $invoice->load('items'),
        ]);
    }

    public function index(InvoicesDataTable $dataTable)
    {
        $clients = User::role('client')->get();
        $sites = Site::all();
        return $dataTable->render('invoices.index', compact('clients', 'sites'));
    }

    public function update(Request $request, $id) {}

    public function edit($id)
    {
        $client = User::with('site')->find($id);
        $sites = $client->site;
        return response()->json(['client' => $client, 'sites' => $sites]);
    }

    // In your InvoiceController
    public function show(Invoice $invoice, $id)
    {
        $invoice = Invoice::where('id', $id)->first();

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

            // Leave / special hours
            $totalSSPHours = $invoice->items->sum('ssp_hours');
            $totalHolidayHours = $invoice->items->sum('holiday_hours');
            $totalUnpaidHours = $invoice->items->sum('unpaid_hours');

            $sspAmount = $invoice->items->sum('ssp_amount');
            $holidayAmount = $invoice->items->sum('holiday_amount');
            $unpaidAmount = $invoice->items->sum('unpaid_amount');

            $invoice->gross_amount = $invoice->items->sum('amount') + $sspAmount + $holidayAmount;
            $invoice->net_amount = $invoice->gross_amount - $unpaidAmount;
        } else {
            // Ensure leave totals are also calculated even if shift hours exist
            $totalSSPHours = $invoice->items->sum('ssp_hours');
            $totalHolidayHours = $invoice->items->sum('holiday_hours');
            $totalUnpaidHours = $invoice->items->sum('unpaid_hours');

            $sspAmount = $invoice->items->sum('ssp_amount');
            $holidayAmount = $invoice->items->sum('holiday_amount');
            $unpaidAmount = $invoice->items->sum('unpaid_amount');
        }

        return view('invoices.show', [
            'invoice' => $invoice,
            'totalHours' => $invoice->items->sum(function ($item) {
                return $item->hours + $item->break_hours + $item->book_on_hours + $item->book_off_hours;
            }),
            'totalBreaks' => $invoice->items->sum('break_hours'),
            'totalBookOnHours' => $invoice->items->sum('book_on_hours'),
            'totalBookOffHours' => $invoice->items->sum('book_off_hours'),
            'totalSSPHours' => $totalSSPHours,
            'totalHolidayHours' => $totalHolidayHours,
            'totalUnpaidHours' => $totalUnpaidHours,
            'sspAmount' => $sspAmount,
            'holidayAmount' => $holidayAmount,
            'unpaidAmount' => $unpaidAmount,
        ]);
    }

    public function delete($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
        ]);

        Invoice::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected invoices deleted.']);
    }
}
