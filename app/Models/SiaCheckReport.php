<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiaCheckReport extends Model
{
    protected $fillable = [
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
