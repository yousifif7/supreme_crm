<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'user_name',
        'action',
        'description',
    ];

    /**
     * Get the parent loggable model (Client, Employee, etc.).
     */
    public function loggable()
    {
        return $this->morphTo();
    }
}
