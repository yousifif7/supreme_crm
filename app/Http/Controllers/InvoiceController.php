<?php

namespace App\Http\Controllers;

use App\DataTables\InvoicesDataTable;
use App\Models\Client;
use App\Models\EmployeeType;
use App\Models\Invoice;
use App\Models\Site;
use App\Models\Shift;
use App\Models\ShiftDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use App\Http\Requests\GenerateInvoiceRequest;
use App\Services\InvoiceService;
use App\Models\User;


class InvoiceController extends Controller
{

    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function generateClientInvoice(GenerateInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->generateClientInvoice(
            $request->client_id,
            $request->site_id,
            $request->date_from,
            $request->date_to,
            $request->due_date,
            $request->notes
        );

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
        return $dataTable->render('invoices.index');
    }

  

    public function update(Request $request, $id)
    {

    }

    public function edit($id)
    {
        $client = User::with('site')->find($id);
        $sites = $client->site;
        return response()->json(['client' => $client, 'sites' => $sites]);
    }

   // In your InvoiceController
public function show(Invoice $invoice,$id)
{
    $invoice=Invoice::where('id',$id)->first();
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
        $invoice->total_deductions_hours = $invoice->items->sum(function($item) {
            return $item->break_hours + $item->book_on_hours + $item->book_off_hours;
        });
        $invoice->gross_amount = $invoice->items->sum('amount');
        $invoice->net_amount = $invoice->items->sum('amount'); // Adjust if you have deductions
    }

    return view('invoices.show', [
        'invoice' => $invoice,
        'totalHours' => $invoice->items->sum(function($item) {
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
