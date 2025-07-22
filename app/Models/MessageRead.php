<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    //
    public $timestamps =false;
    protected $fillable=[
        'user_id',
        'message_id',
        'read_at',
    ];
}
