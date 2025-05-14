<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcontractor extends Model
{
    protected $table = 'sub_contractors';
    protected $fillable = ['company_name'];
}
