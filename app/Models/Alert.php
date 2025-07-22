<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'guard_id',
        'message',
        'priority',
        'trigger_alarm',
        'sent_by_user_id',
    ];

    protected $casts = [
        'trigger_alarm' => 'boolean',
    ];

    public function guard_id()
    {
        return $this->belongsTo(User::class, 'guard_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
