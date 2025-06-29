<?php
// app/Imports/AlertReminderImport.php

namespace App\Imports;

use App\Models\AlertReminder;
use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AlertReminderImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $vehicle = Vehicle::where('registration_number', $row['vehicle'])->first();

        return new AlertReminder([
            'vehicle_id' => $vehicle ? $vehicle->id : null,
            'mot_due_date' => $row['mot_due_date'],
            'insurance_renewal_date' => $row['insurance_renewal_date'],
            'tax_renewal_date' => $row['tax_renewal_date'],
            'service_due_date' => $row['service_due_date'],
            'tachograph_calibration_date' => $row['tachograph_calibration_date'],
        ]);
    }
}
