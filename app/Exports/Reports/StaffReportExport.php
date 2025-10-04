<?php

namespace App\Exports\Reports;

use App\Models\Employee;
use App\Models\Subcontractor;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Collection;

class StaffReportExport implements FromView
{
    protected $staff;

    public function __construct(Collection $staff)
    {
        $this->staff = $staff;
    }

    public function view(): View
    {
        return view('reports.exports.staff_report_excel', [
            'staff' => $this->staff
        ]);
    }
}
