<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftCheckpoint extends Model
{
    protected  $fillable = ['staff_id', 'shift_id', 'checkpoint_time', 'checkpoint_name'];
    
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    public function staff()
    {
        return $this->belongsTo(Employee::class, 'staff_id');
    }
}
