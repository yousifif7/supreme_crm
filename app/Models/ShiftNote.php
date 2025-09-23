<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftNote extends Model
{
    protected $fillable = ['shift_date_id', 'note_type', 'note', 'user_id'];

    public function shiftDate()
    {
        return $this->belongsTo(ShiftDate::class,'shift_date_id');
    }
}
