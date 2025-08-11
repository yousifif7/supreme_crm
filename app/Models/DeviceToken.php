<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id', 'push_token', 'platform'
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
}
