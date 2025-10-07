<?php

namespace App\Models;

use App\Models\CheckpointScan;
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
        // Adjust the foreign key name to whatever is in your DB
        return $this->hasMany(CheckpointScan::class, 'patrol_checkpoint_id');
    }
}
