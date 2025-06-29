<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Invoice::select(
            'invoices.invoice_no',
            'invoices.invoice_title',
            'clients.client_name',
            'sites.site_name',
            'invoices.invoice_date',
            'invoices.due_date',
            'invoices.total_shift_hours',
            'invoices.net_amount',
            'invoices.paid_amount',
            'invoices.payment_date'
        )
        ->join('sites', 'sites.id', '=', 'invoices.site_group_id')
        ->join('clients', 'clients.id', '=', 'invoices.client_id')
        ->get();
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Invoice Title',
            'Client Name',
            'Site Name',
            'Invoice Date',
            'Due Date',
            'Total Shift Hours',
            'Net Amount',
            'Paid Amount',
            'Payment Date',
        ];
    }
}
