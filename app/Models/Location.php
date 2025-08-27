<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'timestamp',
        'on_duty',
        'shiftdate_id'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'timestamp' => 'datetime',
        'on_duty' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shiftdate()
    {
        return $this->belongsTo(shiftDate::class,'shiftdate_id');
    }
}
