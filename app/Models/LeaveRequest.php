<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    //
    protected $fillable = [
        'user_id',           // links to the app user
        'employee_id',       // direct link to Employee for payroll
        'start_date',
        'end_date',
        'reason',
        'emergency',
        'status',            // pending, approved, denied
        'type',              // Sick, Annual, Unpaid, Other
        'paid',              // boolean flag if leave is paid
        'hours',             // requested hours
        'approved_hours',    // approved hours (may differ if partially unpaid)
        'auto_split',        // system split into paid/unpaid
        'ssp_paid_days',     // for Sick Leave: paid days
        'unpaid_days',       // waiting days or excess leave
        'amount_paid',       // monetary value calculated
    ];

    public function user()
    {
        $this->belongsTo(User::class, 'user_id');
    }
    public function employee()
    {
        $this->belongsTo(Employee::class, 'employee_id');
    }
}
