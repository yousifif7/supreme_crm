<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'type',
        'title',
        'message',
        'read',
        'data',
        'action_url'
    ];

    protected $casts = [
        'read' => 'boolean',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function sendNotification($userId, $employeeId, $type, $eventId, $title, $message)
    {
        return Notification::create([
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'type' => $type,
            'event_id' => $eventId,
            'title' => $title,
            'message' => $message,
            'read' => false,
        ]);
    }
}
