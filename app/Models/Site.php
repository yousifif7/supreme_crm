<?php

namespace App\Models;

use App\Traits\LogsChanges;
use App\Models\TrainingMaterial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes, LogsChanges;
    protected $fillable = ['client_id', 'site_name', 'guard_names', 'address', 'post_code', 'site_code', 'contact_number', 'contact_person', 'note', 'manager_1_id', 'manager_2_id', 'start_time', 'end_time', 'break_time', 'guard_rate', 'office_rate', 'billable_rate', 'payable_rate', 'has_qr', 'nfc_tag'];

    protected $casts = [
        'has_qr' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    public function employeeTypes()
    {
        return $this->belongsToMany(EmployeeType::class)->withPivot('guard_rate', 'office_rate');
    }

    public function checkpoints()
    {
        return $this->hasMany(PatrolCheckPoint::class, 'site_id');
    }

    public function shifts() {
        return $this->hasMany(Shift::class);
    }
    public function logs()
{
    return $this->morphMany(Log::class, 'loggable');
}
    
    public function trainings()
    {
        return $this->hasMany(TrainingMaterial::class, 'site_id');
    }
}
