<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use BelongsToAdmin;

    protected $fillable = [
        'admin_id',
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
