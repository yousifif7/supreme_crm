<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckCall extends Model
{
    //
    
    protected $fillable = [
        'shift_id', 'scheduled_time', 'status', 'method','employee_id','name'
    ];

    public function shiftDate()
    {
        return $this->belongsTo(ShiftDate::class,'shift_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
