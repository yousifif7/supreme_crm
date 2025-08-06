<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAcknowledgement extends Model
{
    
    protected $fillable = [
    'user_id',
    'training_material_id',
    'acknowledged_at',
    'completion_time_seconds',
];
}
