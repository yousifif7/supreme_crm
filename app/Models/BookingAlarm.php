<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingAlarm extends Model
{
    //
    protected $fillable = [
        'user_id', 'shift_id', 'type', 'scheduled_time', 'alarm_time', 'acknowledged'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function shift() {
        return $this->belongsTo(Shift::class);
    }
}
