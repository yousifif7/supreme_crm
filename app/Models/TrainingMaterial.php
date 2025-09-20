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
        'implementation_date',
        'deadline',
        'acknowledge_by_date',
    ];


    public function acknowledgedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'training_acknowledgements',
            'training_material_id',
            'user_id'
        )->withTimestamps();
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
