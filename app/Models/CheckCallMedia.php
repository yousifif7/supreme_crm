<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckCallMedia extends Model
{
    //
    protected $fillable = ['check_call_id', 'media_path'];

    public function checkCall()
    {
        return $this->belongsTo(CheckCall::class);
    }
}
