<?php

namespace App\Exports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\FromCollection;

class SitesExport implements FromCollection
{
    public function collection()
    {
        return Site::select(
            'client_id',
            'site_name',
            'site_group',
            'address',
            'post_code',
            'site_code',
            'contact_number',
            'note',
            'manager_1_id',
            'manager_2_id',
            'start_time',
            'end_time',
            'break_time',
            'guard_rate',
            'office_rate',
            'billable_rate',
            'payable_rate'
        )->get();
    }
}
