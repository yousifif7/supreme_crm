<?php

// app/Models/Restriction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restriction extends Model
{
    protected $fillable = [
        'entity_type', 'restriction_type', 'field_name', 'error_message', 'is_active'
    ];
}
