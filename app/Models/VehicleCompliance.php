<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleCompliance extends Model
{
    use SoftDeletes, LogsChanges, BelongsToAdmin;
    protected $fillable = [
        'admin_id',
        'vehicle_id',
        'mot_certificate_number',
        'mot_expiry_date',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry_date',
        'vehicle_tax_status',
        'tax_expiry_date',
        'tax_class',
        'v5c_logbook_reference_number',
        'lez_ulez_compliant',
        'tachograph_certificate_number',
        'tachograph_calibration_expiry',
    ];
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
