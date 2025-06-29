<?php

namespace App\Imports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VehiclesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Vehicle([
            'registration_number'     => $row['registration_number'],
            'make'                    => $row['make'],
            'model'                   => $row['model'],
            'year_of_manufacture'     => $row['year_of_manufacture'],
            'colour'                  => $row['colour'],
            'body_type'               => $row['body_type'],
            'fuel_type'               => $row['fuel_type'],
            'engine_size'             => $row['engine_size'],
            'vin'                     => $row['vin'],
            'odometer_reading'        => $row['odometer_reading'],
            'first_registration_date' => $row['first_registration_date'],
            'vehicle_category'        => $row['vehicle_category'],
            'assigned_to'             => $row['assigned_to'],
        ]);
    }
}
