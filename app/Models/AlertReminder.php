<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertReminder extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = [
        'vehicle_id',
        'mot_due_date',
        'insurance_renewal_date',
        'tax_renewal_date',
        'service_due_date',
        'tachograph_calibration_date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
