<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    protected $fillable = ['name'];
    public function sites()
    {
        return $this->belongsToMany(Site::class);
    }
}
