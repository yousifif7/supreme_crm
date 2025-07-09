<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = ['client_id', 'site_name', 'guard_names', 'address', 'post_code', 'site_code', 'contact_number', 'contact_person', 'note', 'manager_1_id', 'manager_2_id', 'start_time', 'end_time', 'break_time', 'guard_rate', 'office_rate', 'billable_rate', 'payable_rate'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function employeeTypes()
    {
        return $this->belongsToMany(EmployeeType::class)->withPivot('guard_rate', 'office_rate');
    }
}
