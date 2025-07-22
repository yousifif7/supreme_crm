<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoadworthinessCheck extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = [
        'vehicle_id',
        'date_completed',
        'checked_by',
        'defects_found',
        'corrective_action_taken',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
