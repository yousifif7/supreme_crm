<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckpointScanMedia extends Model
{
    //
    protected $fillable = ['checkpoint_scan_id', 'file_path'];

    public function scan()
    {
        return $this->belongsTo(CheckpointScan::class);
    }
}
