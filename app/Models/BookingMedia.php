<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingMedia extends Model
{
    protected $fillable = [
        'user_id', 'shift_date_id', 'type', 'file_path', 'original_name', 'file_type', 'file_size'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shiftDate()
    {
        return $this->belongsTo(ShiftDate::class, 'shift_date_id');
    }
}
