<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Shift;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\ShiftDate;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\InvoiceReview;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceAPIController extends Controller
{
    public function shiftHistory(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'invoiced' => 'nullable|boolean',
        ]);

        $query = ShiftDate::where('staff_id', Auth::id())
            ->whereBetween('date', [$request->date_from, $request->date_to]);

        if ($request->has('invoiced')) {
            $query->where('invoiced', $request->invoiced);
        }

        return response()->json([
            'shifts' => $query->get([
                'id as shift_id',
                'site_name',
                'date',
                'hours_worked',
                'base_rate',
                'travel_time',
                'travel_rate',
                'total_amount',
                'invoiced'
            ])
        ]);
    }

    public function submitInvoice(Request $request)
    {
        $validated = $request->validate([
            'shift_ids' => 'required|array',
            'invoice_period.date_from' => 'required|date',
            'invoice_period.date_to' => 'required|date|after_or_equal:invoice_period.date_from',
            'total_amount' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        $invoice = Invoice::create([
            'security_staff_id' => Auth::id(),
            'date_from' => $validated['invoice_period']['date_from'],
            'date_to' => $validated['invoice_period']['date_to'],
            'total_amount' => $validated['total_amount'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'submitted',
            'type' => 'security_staff',
            'issue_date' => Carbon::now(),
            'due_date' => now()->addDays(30), // default to 30 days from today
            // 'total_amount' => 0.00,
            'tax_amount' => 0.00,
        ]);

        $shift = ShiftDate::whereIn('id', $validated['shift_ids'])
            ->where('staff_id', Auth::id())
            ->update([
                'invoice_id' => $invoice->id,
                'invoiced' => true
            ]);

        $user = Auth::user(); // Get the authenticated user
        $employee = Employee::where('user_id', $user->id)->first();

        return response()->json([
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'submitted_at' => $invoice->submitted_at
        ]);
    }


    public function getPayrolls(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:submitted,under_review,approved,paid',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $query = Invoice::where('security_staff_id', Auth::id());

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate($request->limit ?? 10);

        // Transform the collection inside paginator
        $invoices->getCollection()->transform(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT),
                'period' => optional($invoice->start_date)->format('Y-m-d') . ' to ' . optional($invoice->end_date)->format('Y-m-d'),
                'amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'admin_review' => $invoice->adminReview ? [
                    'revised_amount' => $invoice->adminReview->revised_amount,
                    'revision_reason' => $invoice->adminReview->revision_reason,
                    'requires_confirmation' => $invoice->adminReview->requires_confirmation,
                ] : null,
                'submitted_at' => optional($invoice->issue_date)->toDateTimeString(),
                'paid_at' => optional($invoice->paid_at)->toDateTimeString(),
            ];
        });

        return response()->json([
			//'invoices' => $invoices->items(),
			'invoices' => [],
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function confirmRevision(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'accepted' => 'required|boolean',
            'notes' => 'nullable|string'
        ]);

        $review = $invoice->adminReview;

        if (!$review || !$review->requires_confirmation) {
            return response()->json(['message' => 'No revision to confirm.'], 400);
        }

        $review->update([
            'accepted' => $validated['accepted'],
            'notes' => $validated['notes']
        ]);

        if ($validated['accepted']) {
            $invoice->update([
                'total_amount' => $review->revised_amount,
                'status' => 'approved'
            ]);
        }

        return response()->json(['message' => 'Invoice revision ' . ($validated['accepted'] ? 'accepted' : 'rejected')]);
    }

    public function exportPayrollPdf($invoiceId)
    {
        $invoice = Invoice::with(['securityStaff', 'employee', 'items.site', 'subcontractor'])->findOrFail($invoiceId);

        // Resolve payee user & employee record (mirrors PayrollController logic)
        $staff    = null;
        $payeeUser = null;
        if (! empty($invoice->security_staff_id)) {
            $staff     = \App\Models\Employee::where('user_id', $invoice->security_staff_id)->first();
            $payeeUser = $staff?->user ?? \App\Models\User::find($invoice->security_staff_id);
        } elseif (! empty($invoice->subcontractor_id)) {
            $payeeUser = $invoice->subcontractor;
        }

        $totalHours       = $invoice->items->sum(fn($i) => $i->hours + $i->break_hours + $i->book_on_hours + $i->book_off_hours);
        $totalBreaks      = $invoice->items->sum('break_hours');
        $totalBookOn      = $invoice->items->sum('book_on_hours');
        $totalBookOff     = $invoice->items->sum('book_off_hours');
        $sspAmount        = $invoice->ssp_amount        ?? 0;
        $sspDays          = $invoice->ssp_days          ?? 0;
        $holidayAmount    = $invoice->holiday_amount    ?? 0;
        $holidayHours     = $invoice->holiday_hours     ?? 0;
        $unpaidAmount     = $invoice->unpaid_leave_amount ?? 0;
        $unpaidHours      = $invoice->unpaid_leave_hours  ?? 0;

        $pdf = Pdf::loadView('invoices.payroll_pdf', compact(
            'invoice', 'staff', 'payeeUser',
            'totalHours', 'totalBreaks', 'totalBookOn', 'totalBookOff',
            'sspAmount', 'sspDays', 'holidayAmount', 'holidayHours',
            'unpaidAmount', 'unpaidHours'
        ));

        return $pdf->download('Payroll_' . $invoice->invoice_number . '.pdf');
    }
}
