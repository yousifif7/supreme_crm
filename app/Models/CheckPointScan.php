<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckpointScan extends Model
{
    //
    protected $fillable = [
        'patrol_checkpoint_id', 'user_id', 'scan_data', 'scan_method',
        'latitude', 'longitude', 'notes', 'issues_found', 'timestamp'
    ];

    public function checkpoint()
    {
        return $this->belongsTo(PatrolCheckpoint::class);
    }

    public function media()
    {
        return $this->hasMany(CheckpointScanMedia::class);
    }
}
