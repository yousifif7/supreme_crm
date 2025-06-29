<?php

namespace App\Imports;

use App\Models\Maintenance;
use App\Models\VehicleMaintenance;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VehicleMaintenanceImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new VehicleMaintenance([
            'vehicle_id'              => $row['vehicle_id'],
            'last_service_date'       => $row['last_service_date'],
            'next_service_due_date'   => $row['next_service_due_date'],
            'work_type'               => $row['work_type'],
            'maintenance_date'        => $row['maintenance_date'],
            'garage_provider'         => $row['garage_provider'],
            'reported_by'             => $row['reported_by'],
            'date_reported'           => $row['date_reported'],
            'resolution_status'       => $row['resolution_status'],
        ]);
    }
}
