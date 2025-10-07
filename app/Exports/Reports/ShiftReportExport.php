<?php

namespace App\Exports\Reports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Collection;

class ShiftReportExport implements FromView
{
    protected $shiftDates;

    public function __construct(Collection $shiftDates)
    {
        $this->shiftDates = $shiftDates;
    }

    public function view(): View
    {
        return view('reports.exports.shift_report_excel', [
            'shiftDates' => $this->shiftDates
        ]);
    }
}
