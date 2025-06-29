<?php

namespace App\Imports;

use App\Models\RoadworthinessCheck;
use Maatwebsite\Excel\Concerns\ToModel;

class checkImport implements ToModel
{
    public function model(array $row)
    {
        return new RoadworthinessCheck([
            'vehicle_id'              => $row['vehicle_id'],
            'date_completed'       => $row['date_completed'],
            'checked_by'   => $row['checked_by'],
            'defects_found'               => $row['defects_found'],
            'corrective_action_taken'        => $row['corrective_action_taken'],
        ]);
    }
}
