<?php

namespace App\Exports\Reports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AvailabilityReport implements FromView, ShouldAutoSize
{
    protected $availabilities;

    public function __construct($availabilities)
    {
        $this->availabilities = $availabilities;
    }

    public function view(): View
    {
        return view('reports.exports.availability_report_excel', [
            'availabilities' => $this->availabilities,
        ]);
    }
}
