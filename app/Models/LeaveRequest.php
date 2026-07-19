<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToAdmin;

class LeaveRequest extends Model
{
    use BelongsToAdmin;

    protected $fillable = [
        'admin_id',
        'user_id',           // links to the app user
        'employee_id',       // direct link to Employee for payroll
        'start_date',
        'end_date',
        'reason',
        'shift_id',
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
        return $this->belongsTo(User::class, 'user_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(ShiftDate::class, 'shift_id');
    }

    public function logs()
{
    return $this->morphMany(Log::class, 'loggable');
}
}
