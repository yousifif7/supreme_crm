<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftDate extends Model
{
    use SoftDeletes, LogsChanges;
    protected $table = 'shift_dates';
    protected  $fillable = ['staff_id', 'shift_id', 'shift_date', 'start_time', 'end_time', 'total_hours', 'break_time', 'absentee_end', 'absentee_start_time', 'absentee_end_time', 'is_assign'];
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    public function staff()
    {
        return $this->belongsTo(Employee::class, 'staff_id');
    }
}
