<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patrol extends Model
{
    //
    protected $fillable = [
        'shift_id', 'name', 'summary', 'total_checkpoints','start_time',
        'completed_checkpoints', 'issues_reported', 'started_at' ,'completed_at',
    ];

    public function shift()
    {
        return $this->belongsTo(ShiftDate::class);
    }

}
