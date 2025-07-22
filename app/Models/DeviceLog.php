<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    //
        protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'os',
        'app_version',
        'latitude',
        'longitude',
    ];
}
