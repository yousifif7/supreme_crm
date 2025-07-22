<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patrol extends Model
{
    //
    protected $fillable = [
        'shift_id', 'name', 'summary', 'total_checkpoints',
        'completed_checkpoints', 'issues_reported', 'completed_at'
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function checkpoints()
    {
        return $this->hasMany(PatrolCheckpoint::class);
    }
}
