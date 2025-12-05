<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileChangeRequest extends Model
{
    protected $table = 'profile_change_requests';

    protected $fillable = [
        'user_id',
        'requested_email',
        'old_email',
        'status',
        'admin_id',
        'admin_note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
