<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alarm extends Model
{
    protected $fillable = [
        'user_id',         // who the alarm belongs to (optional)
        'description',     // alarm details
        'triggered_at',    // datetime when alarm triggered
        'override_reason', // admin override reason
        'resolved',        // boolean resolved flag
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved' => 'boolean',
    ];
}
