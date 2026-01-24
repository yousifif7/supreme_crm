<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;

class CheckCall extends Model
{
    use LogsChanges;
    
    protected $fillable = [
        'shift_id', 'scheduled_time', 'status', 'method','employee_id','name', 'require_media','completed_at', 'notes','approval_status'
    ];

    public function shiftDate()
    {
        return $this->belongsTo(ShiftDate::class,'shift_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
