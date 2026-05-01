<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;

class SiaCheckReport extends Model
{
    use BelongsToAdmin;

    protected $fillable = [
        'admin_id',
        'run_id',
        'employee_id',
        'employee_name',
        'sia_licence',
        'status_before',
        'status_after',
        'changed',
        'error',
        'checked_at',
    ];

    protected $casts = [
        'changed'    => 'boolean',
        'checked_at' => 'datetime',
    ];

    /** All entries belonging to the same run */
    public function scopeForRun($query, string $runId)
    {
        return $query->where('run_id', $runId);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
