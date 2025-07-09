<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = [
        'registration_number',
        'make',
        'model',
        'year_of_manufacture',
        'colour',
        'body_type',
        'fuel_type',
        'engine_size',
        'vin',
        'odometer_reading',
        'first_registration_date',
        'vehicle_category',
        'assigned_to',
    ];
}
