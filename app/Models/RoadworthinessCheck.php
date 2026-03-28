<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoadworthinessCheck extends Model
{
    use SoftDeletes, LogsChanges, BelongsToAdmin;
    protected $fillable = [
        'admin_id',
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
