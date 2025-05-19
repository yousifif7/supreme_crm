<?php

namespace App\Imports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SitesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Site([
            'client_id'      => $row['client_id'],
            'site_name'      => $row['site_name'],
            'site_group'     => $row['site_group'],
            'address'        => $row['address'],
            'post_code'      => $row['post_code'],
            'site_code'      => $row['site_code'],
            'contact_number' => $row['contact_number'],
            'note'           => $row['note'],
            'manager_1_id'   => $row['manager_1_id'],
            'manager_2_id'   => $row['manager_2_id'],
            'start_time'     => $row['start_time'],
            'end_time'       => $row['end_time'],
            'break_time'     => $row['break_time'],
            'guard_rate'     => $row['guard_rate'],
            'office_rate'    => $row['office_rate'],
            'billable_rate'  => $row['billable_rate'],
            'payable_rate'   => $row['payable_rate'],
        ]);
    }
}
