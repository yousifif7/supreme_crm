<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes, LogsChanges;
    protected $table = 'company';
    protected $fillable = ['company_name', 'company_address'];
}
