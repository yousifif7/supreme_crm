<?php

namespace App\Exports;

use App\Models\Shift;
use Maatwebsite\Excel\Concerns\FromCollection;

class ShiftDateExport implements FromCollection
{
    public function collection()
    {
        return Shift::all();
    }
}
