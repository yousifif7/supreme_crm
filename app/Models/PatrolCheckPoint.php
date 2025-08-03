<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrolCheckpoint extends Model
{
    protected $table = 'patrol_check_points';
    //
    protected $fillable = [
        'patrol_id', 'name', 'qr_code', 'nfc_tag',
        'latitude', 'longitude', 'required'
    ];

    public function patrol()
    {
        return $this->belongsTo(Patrol::class);
    }

    public function scans()
    {
        return $this->hasMany(CheckpointScan::class);
    }
}
