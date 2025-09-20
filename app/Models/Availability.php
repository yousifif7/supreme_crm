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
        return \Carbon\Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    public function getEndTimeAttribute($value)
    {
        return \Carbon\Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }
}
