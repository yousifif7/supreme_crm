<?php

namespace App\Imports;

use App\Models\VehicleCompliance;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VehicleComplianceImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new VehicleCompliance([
            'mot_certificate_number'         => $row['mot_certificate_number'],
            'mot_expiry_date'                => $row['mot_expiry_date'],
            'insurance_provider'             => $row['insurance_provider'],
            'insurance_policy_number'        => $row['insurance_policy_number'],
            'insurance_expiry_date'          => $row['insurance_expiry_date'],
            'vehicle_tax_status'             => $row['vehicle_tax_status'],
            'tax_expiry_date'                => $row['tax_expiry_date'],
            'tax_class'                      => $row['tax_class'],
            'v5c_logbook_reference_number'   => $row['v5c_logbook_reference_number'],
            'lez_ulez_compliant'             => $row['lez_ulez_compliant'],
            'tachograph_certificate_number'  => $row['tachograph_certificate_number'],
            'tachograph_calibration_expiry'  => $row['tachograph_calibration_expiry'],
        ]);
    }
}
