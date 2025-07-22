<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyContacts extends Model
{
    
    protected $fillable = [
        'profile_id',
        'name',
        'phone',
        'relationship',
    ];
}
