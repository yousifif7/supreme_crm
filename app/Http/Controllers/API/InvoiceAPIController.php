<?php

namespace App\Http\Controllers\API;

use App\Models\Shift;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\InvoiceReview;
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

        $query = Shift::where('user_id', Auth::id())
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
            'invoice_period.start_date' => 'required|date',
            'invoice_period.end_date' => 'required|date|after_or_equal:invoice_period.start_date',
            'total_amount' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        $invoice = Invoice::create([
            'user_id' => Auth::id(),
            'start_date' => $validated['invoice_period']['start_date'],
            'end_date' => $validated['invoice_period']['end_date'],
            'total_amount' => $validated['total_amount'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now()
        ]);

        Shift::whereIn('id', $validated['shift_ids'])
            ->where('user_id', Auth::id())
            ->update([
                'invoice_id' => $invoice->id,
                'invoiced' => true
            ]);

        return response()->json([
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'submitted_at' => $invoice->submitted_at
        ]);
    }

    public function getInvoices(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:submitted,under_review,approved,paid',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $query = Invoice::with('adminReview')
            ->where('user_id', Auth::id());

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
                'submitted_at' => optional($invoice->submitted_at)->toDateTimeString(),
                'paid_at' => optional($invoice->paid_at)->toDateTimeString(),
            ];
        });

        return response()->json([
            'invoices' => $invoices->items(),
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
}
