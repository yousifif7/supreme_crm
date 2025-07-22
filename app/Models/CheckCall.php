<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckCall extends Model
{
    //
    
    protected $fillable = [
        'shift_id', 'scheduled_time', 'status', 'method',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
