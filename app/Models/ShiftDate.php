<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftDate extends Model
{
    protected $table = 'shift_dates';
    protected  $fillable = ['shift_id', 'shift_date', 'start_time', 'end_time', 'total_hours', 'break_time', 'absentee_end', 'absentee_start_time', 'absentee_end_time', 'is_assign'];
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
