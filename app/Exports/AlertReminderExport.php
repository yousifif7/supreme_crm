<?php
// app/Exports/AlertReminderExport.php

namespace App\Exports;

use App\Models\AlertReminder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AlertReminderExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return AlertReminder::with('vehicle')->get()->map(function ($reminder) {
            return [
                'Vehicle' => $reminder->vehicle->registration_number ?? 'N/A',
                'MOT Due Date' => $reminder->mot_due_date,
                'Insurance Renewal Date' => $reminder->insurance_renewal_date,
                'Tax Renewal Date' => $reminder->tax_renewal_date,
                'Service Due Date' => $reminder->service_due_date,
                'Tachograph Calibration Date' => $reminder->tachograph_calibration_date,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Vehicle',
            'MOT Due Date',
            'Insurance Renewal Date',
            'Tax Renewal Date',
            'Service Due Date',
            'Tachograph Calibration Date',
        ];
    }
}
