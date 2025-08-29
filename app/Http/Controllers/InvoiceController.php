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

        // ✅ Overlap check
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
            if ($request->ajax()) {
                return response()->json([
                    'errors' => ['date_from' => ['An invoice already exists for this date range.']]
                ], 422);
            }

            return redirect()->back()
                ->withErrors(['date_from' => ['An invoice already exists for this date range.']])
                ->withInput();
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
            if ($request->ajax()) {
                return response()->json([
                    'errors' => [
                        'shift' => ['No shifts were found for the selected date range. Please adjust your dates or make sure shifts exist before generating an invoice.']
                    ]
                ], 422);
            }

            return redirect()->back()
                ->withErrors(['shift' => ['No shifts were found for the selected date range.']])
                ->withInput();
        }

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
            $invoice->gross_amount = $invoice->items->sum('amount');
            $invoice->net_amount = $invoice->items->sum('amount'); // Adjust if you have deductions
        }

        return view('invoices.show', [
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
