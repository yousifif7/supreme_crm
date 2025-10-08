<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalaryReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
     protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function array(): array
    {
        $rows = [];
        /** @var \Illuminate\Database\Eloquent\Collection $invoices */
        $invoices = $this->payload['invoices'] ?? collect();
        foreach ($invoices as $inv) {
            $rows[] = [
                $inv->invoice_number,
                optional($inv->issue_date)->toDateString() ?? $inv->issue_date,
                optional($inv->date_from)->toDateString() ?? $inv->date_from,
                optional($inv->date_to)->toDateString() ?? $inv->date_to,
                $inv->site?->site_name ?? '',
                $inv->total_shift_hours ?? $inv->total_duration_hours ?? 0,
                $inv->gross_amount ?? 0,
                $inv->net_amount ?? 0,
            ];
        }

        // add totals row
        $totals = $this->payload['totals'] ?? [];
        $rows[] = [];
        $rows[] = ['Totals', '', '', '', '', $totals['hours'] ?? 0, $totals['gross'] ?? 0, $totals['net'] ?? 0];

        return $rows;
    }

    public function headings(): array
    {
        return ['Invoice #', 'Issue Date', 'Period From', 'Period To', 'Site', 'Worked Hours', 'Gross', 'Net'];
    }
}
