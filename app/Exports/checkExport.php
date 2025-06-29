<?php

namespace App\Exports;

use App\Models\RoadworthinessCheck;
use Maatwebsite\Excel\Concerns\FromCollection;

class checkExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return RoadworthinessCheck::all();
    }
}
