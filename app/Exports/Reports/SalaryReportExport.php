<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalaryReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function array(): array
    {
        $report = $this->payload['report'] ?? null;
        $p = $report['payroll'] ?? [];
        $rows = [];

        $rows[] = ['Staff', $report['staff']->name ?? ''];
        $rows[] = ['Period', $report['date_from']->toDateString() . ' - ' . $report['date_to']->toDateString()];
        $rows[] = [];
        $rows[] = ['Rate per hour', $p['rate'] ?? ''];
        $rows[] = ['Total shift hours', $p['total_hours'] ?? ''];
        $rows[] = ['Break hours', $p['total_breaks'] ?? ''];
        $rows[] = ['Gross amount', $p['gross_amount'] ?? ''];
        $rows[] = ['Net amount', $p['net_amount'] ?? ''];
        $rows[] = [];
        $rows[] = ['Notes', $report['employee']->notes ?? ''];

        return $rows;
    }

    public function headings(): array
    {
        return ['Field', 'Value'];
    }
}
