<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    //
    protected $fillable = [
        'user_id',
        'shift_id',
        'category',
        'severity',
        'title',
        'description',
        'location',
        'police_notified',
        'police_reference',
        'immediate_action_taken',
        'status',
    ];

    public function media()
    {
        return $this->hasMany(IncidentMedia::class);
    }

    public function people()
    {
        return $this->hasMany(IncidentPerson::class);
    }
}
