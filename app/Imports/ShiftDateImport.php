<?php

namespace App\Imports;

use App\Models\Shift;
use Maatwebsite\Excel\Concerns\ToModel;

class ShiftDateImport implements ToModel
{
    public function model(array $row)
    {
        return new Shift([
            'staff_id' => $row[0],
            'shift_date'   => $row[1],
            'start_shift' => $row[2],
            'start_time'   => $row[3],
            'end_time'   => $row[3],
            'total_hours'   => $row[3],
            'break_time'   => $row[3],
            'absentee_end'   => $row[3],
            'absentee_start_time'   => $row[3],
            'absentee_end_time'   => $row[3],
        ]);
    }
}
