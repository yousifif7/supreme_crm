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

    public function acknowledgedMaterials()
    {
        return $this->belongsToMany(
            TrainingMaterial::class,
            'training_acknowledgements',
            'user_id',
            'training_material_id'
        )->withTimestamps();
    }

        public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainingMaterial()
    {
        return $this->belongsTo(TrainingMaterial::class);
    }
}
