<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToAdmin;

class Payroll extends Model
{
    use BelongsToAdmin;

    protected $fillable = [
        'admin_id',
    ];
}
