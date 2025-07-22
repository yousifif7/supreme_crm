<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = ['name'];
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
