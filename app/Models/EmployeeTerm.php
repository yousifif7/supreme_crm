<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTerm extends Model
{
    protected $tables = 'employee_terms';
    protected $fillable = ['term_name', 'employee_id', 'from_date', 'to_date'];
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
