<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceChangeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'old_device_id',
        'new_device_id',
        'new_device_name',
        'new_os',
        'new_app_version',
        'status',
        'admin_note',
        'approved_at',
        'rejected_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
