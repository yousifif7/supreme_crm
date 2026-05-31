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
     * - a data array with key: 'stats' (collection/array)
     */
    public function __construct($data = [])
    {
        if (!empty($data) && is_array($data) && isset($data[0]) && is_array($data[0])) {
            $this->rows = $data;
            $this->headings = $data[0];
            return;
        }

        $stats = $data['stats'] ?? collect();

        $this->headings = [
            'Staff',
            'Completed Checkcalls',
            'Missed Checkcalls',
            'Completed Patrols',
            'Missed Patrols',
        ];

        foreach ($stats as $row) {
            $this->rows[] = [
                $row['staff_name'] ?? '',
                $row['completed_checkcalls'] ?? 0,
                $row['missed_checkcalls'] ?? 0,
                $row['completed_patrols'] ?? 0,
                $row['missed_patrols'] ?? 0,
            ];
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
