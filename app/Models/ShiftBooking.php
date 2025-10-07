<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftBooking extends Model
{
    //
        protected $fillable = [
        'user_id', 'shift_id', 'type', 'face_verification_result',
        'latitude', 'longitude', 'address', 'timestamp'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function shift() {
        return $this->belongsTo(ShiftDate::class ,'shift_id');
    }

    
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
