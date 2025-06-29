<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMaintenance extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = [
        'vehicle_id',
        'last_service_date',
        'next_service_due_date',
        'work_type',
        'maintenance_date',
        'garage_provider',
        'reported_by',
        'date_reported',
        'resolution_status',
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
