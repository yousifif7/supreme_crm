<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $table = 'guard_availabilities';

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    /**
     * Each availability belongs to one user (guard).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor to format times nicely if needed.
     */
    public function getStartTimeAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getEndTimeAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
