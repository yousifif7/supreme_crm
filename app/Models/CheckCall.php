<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckCall extends Model
{
    //
    
    protected $fillable = [
        'shift_id', 'scheduled_time', 'status', 'method','employee_id','name'
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
