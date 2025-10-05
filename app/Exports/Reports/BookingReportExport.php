<?php

namespace App\Exports\Reports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BookingReportExport implements FromView, ShouldAutoSize
{
    protected $bookings;

    public function __construct($bookings)
    {
        $this->bookings = $bookings;
    }

    /**
     * Export bookings as Excel using a Blade view
     */
    public function view(): View
    {
        return view('reports.exports.booking_report_excel', [
            'bookings' => $this->bookings,
        ]);
    }
}
