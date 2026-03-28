<?php

namespace App\Models;

use App\Traits\LogsChanges;
use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes, LogsChanges, BelongsToAdmin;
    protected $fillable = [
        'admin_id',
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
