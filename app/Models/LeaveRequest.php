<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    //
    protected $fillable =['user_id', 'start_date', 'end_date','reason','emergency','status'];

    public function employee(){
        $this->belongsTo(Employee::class, 'user_id');
    }
}
