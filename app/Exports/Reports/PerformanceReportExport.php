<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PerformanceReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected array $rows = [];
    protected array $headings = [];

    /**
     * Constructor accepts either:
     * - an array of rows (already-built), OR
     * - a data array with keys: 'stats' (collection/array), 'statusOptions' (array), 'totals' (array)
     */
    public function __construct($data = [])
    {
        // if already an array of rows (first element is array), use it directly
        if (!empty($data) && is_array($data) && isset($data[0]) && is_array($data[0])) {
            $this->rows = $data;
            $this->headings = $data[0];
            return;
        }

        // else expect associative input
        $stats = $data['stats'] ?? collect();
        $statusOptions = $data['statusOptions'] ?? [];
        $totals = $data['totals'] ?? [];

        $header = ['Staff ID', 'Staff Name', 'Total Shifts', 'Total Hours'];
        foreach ($statusOptions as $code => $label) $header[] = $label;
        $this->headings = $header;

        $rows = [];
        foreach ($stats as $row) {
            $r = [
                $row['staff_id'],
                $row['staff_name'],
                $row['total_shifts'],
                $row['total_hours'],
            ];
            foreach ($statusOptions as $code => $label) {
                $r[] = $row['status_counts'][$code] ?? 0;
            }
            $rows[] = $r;
        }

        // Prepend headings for FromArray compatibility (optional)
        $this->rows[] = $this->headings;
        foreach ($rows as $r) $this->rows[] = $r;

        // Optionally append totals rows (keep columns aligned with status columns)
        $this->rows[] = [];
        $pad = array_fill(0, count($statusOptions), '');
        $totRows = [
            ['Totals', '', $totals['total_shifts_to_client'] ?? ''],
            ['Total Checkcalls', '', $totals['total_checkcalls'] ?? ''],
            ['Completed Checkcalls', '', $totals['total_completed_checkcalls'] ?? ''],
            ['Missed Checkcalls', '', $totals['total_missed_checkcalls'] ?? ''],
            ['Total Patrols', '', $totals['total_patrols'] ?? ''],
            ['Completed Patrols', '', $totals['total_completed_patrols'] ?? ''],
            ['Missed Patrols', '', $totals['total_missed_patrols'] ?? ''],
            ['Completed Shifts', '', $totals['total_completed_shifts'] ?? ''],
        ];
        foreach ($totRows as $r) {
            $this->rows[] = array_merge($r, $pad);
        }
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}