<?php

namespace App\Exports;

use App\Models\VehicleCompliance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehicleComplianceExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return VehicleCompliance::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'MOT Certificate Number',
            'MOT Expiry Date',
            'Insurance Provider',
            'Policy Number',
            'Insurance Expiry Date',
            'Vehicle Tax Status',
            'Tax Expiry Date',
            'Tax Class',
            'V5C Logbook Ref',
            'LEZ/ULEZ Compliant',
            'Tachograph Certificate',
            'Calibration Expiry',
            'Created At',
            'Updated At',
        ];
    }
}
