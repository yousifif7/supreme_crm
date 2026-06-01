<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckpointScan extends Model
{
    protected $fillable = [
        'patrol_id', 'user_id', 'patrol_checkpoint_id', 'scan_data', 'scan_method',
        'latitude', 'longitude', 'notes', 'issues_found', 'timestamp'
    ];

    public function patrol()
    {
        return $this->belongsTo(Patrol::class, 'patrol_id');
    }

    public function media()
    {
        return $this->hasMany(CheckpointScanMedia::class);
    }

    public function checkpoint()
    {
        return $this->belongsTo(\App\Models\PatrolCheckPoint::class, 'patrol_checkpoint_id');
    }

    /**
     * Remove the scan's media rows when the scan itself is deleted (e.g. via the
     * patrol cascade when a shift is deleted), so they aren't left orphaned.
     */
    protected static function booted()
    {
        static::deleting(function (CheckpointScan $scan) {
            $scan->media()->delete();
        });
    }
}
