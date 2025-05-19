<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $fillable = ['client_id', 'site_name', 'site_group', 'address', 'post_code', 'site_code', 'contact_number', 'note', 'manager_1_id', 'manager_2_id', 'start_time', 'end_time', 'break_time', 'guard_rate', 'office_rate', 'billable_rate', 'payable_rate'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function employeeTypes()
    {
        return $this->belongsToMany(EmployeeType::class)->withPivot('guard_rate', 'office_rate');
    }
}
