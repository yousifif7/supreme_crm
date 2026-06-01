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

    public function media()
    {
        return $this->hasMany(CheckCallMedia::class, 'check_call_id');
    }

    public function firstMedia()
    {
        return $this->hasOne(CheckCallMedia::class, 'check_call_id')->oldestOfMany();
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Clean up dependent rows whenever a check call is deleted — from anywhere
     * (shift delete/bulk-delete, the API destroy endpoint, etc.). Without this
     * the media rows were left orphaned in check_call_media.
     */
    protected static function booted()
    {
        static::deleting(function (CheckCall $checkCall) {
            $checkCall->media()->delete();
        });
    }
}
