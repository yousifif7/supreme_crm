<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $tables = 'holidays';
    protected $fillable = ['holidays_entitement', 'employee_id', 'from_date', 'to_date'];
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
