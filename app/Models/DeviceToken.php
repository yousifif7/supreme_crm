<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'employee_id', 'push_token', 'platform'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
