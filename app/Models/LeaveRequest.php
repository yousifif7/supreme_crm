<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    //
        protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'emergency',
        'status',
        'type',
        'paid',
        'hours',
        'approved_hours',
    ];

    public function employee(){
        $this->belongsTo(User::class, 'user_id');
    }
}
