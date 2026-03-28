<?php

namespace App\Models;

use App\Traits\LogsChanges;
use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;

class Patrol extends Model
{
    use LogsChanges, BelongsToAdmin;
    
    protected $fillable = [
        'admin_id',
        'shift_id', 'name', 'summary', 'total_checkpoints','start_time','status', 'approval_status',
        'completed_checkpoints', 'issues_reported', 'started_at' ,'completed_at',
    ];

    public function shift()
    {
        return $this->belongsTo(ShiftDate::class);
    }
    
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
