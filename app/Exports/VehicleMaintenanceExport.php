<?php

namespace App\Exports;

use App\Models\Maintenance;
use App\Models\VehicleMaintenance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehicleMaintenanceExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return VehicleMaintenance::select([
            'id',
            'vehicle_id',
            'last_service_date',
            'next_service_due_date',
            'work_type',
            'maintenance_date',
            'garage_provider',
            'reported_by',
            'date_reported',
            'resolution_status',
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Vehicle ID',
            'Last Service Date',
            'Next Service Due Date',
            'Work Type',
            'Maintenance Date',
            'Garage Provider',
            'Reported By',
            'Date Reported',
            'Resolution Status',
        ];
    }
}
