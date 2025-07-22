<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyAlert extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'address',
        'enable_device_alarm',
        'message',
        'timestamp',
        'acknowledged_by_control',
        'cancelled',
        'cancel_reason',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'enable_device_alarm' => 'boolean',
        'timestamp' => 'datetime',
        'acknowledged_by_control' => 'boolean',
        'cancelled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
