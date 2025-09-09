<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrolCheckPoint extends Model
{
    protected $table = 'patrol_check_points';
    //
    protected $fillable = [
        'site_id', 'name', 'qr_code', 'nfc_tag',
        'latitude', 'longitude', 'required'
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function scans()
    {
        return $this->hasMany(CheckpointScan::class);
    }
}
