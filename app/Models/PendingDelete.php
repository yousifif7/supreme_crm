<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingDelete extends Model
{
    protected $table = 'pending_deletes';

    protected $fillable = [
        'requester_id',
        'target_type',
        'target_id',
        'target_user_id',
        'reason',
        'status',
        'approved_by',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
