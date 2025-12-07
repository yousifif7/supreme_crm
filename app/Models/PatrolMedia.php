<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrolMedia extends Model
{
    protected $fillable = ['patrol_id', 'file_path'];

    public function patrol()
    {
        return $this->belongsTo(Patrol::class);
    }
}
