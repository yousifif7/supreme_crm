<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMaintenance extends Model
{
    use SoftDeletes, LogsChanges, BelongsToAdmin;
    protected $fillable = [
        'admin_id',
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
}
