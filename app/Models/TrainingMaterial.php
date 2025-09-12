<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMaterial extends Model
{


    protected $fillable = [
        'title',
        'type',
        'description',
        'content_url',
        'pdf_url',
        'required',
        'expiry_date',
    ];


    public function acknowledgements()
    {

        return $this->hasMany(TrainingAcknowledgement::class, 'training_material_id');
    }

    public function shiftDates()
    {
        return $this->belongsToMany(
            \App\Models\ShiftDate::class,
            'shift_trainings',
            'training_id',
            'shift_date_id'
        )->withTimestamps();
    }
}
