<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsChanges;

class EmployeeLeave extends Model
{
    use LogsChanges;
        
    protected $fillable = ['leave_entitlement', 'employee_id', 'from_date', 'to_date', 'status', 'approved_at', 'approved_by'];
    
    protected $casts = [
        'approved_at' => 'datetime',
    ];
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
