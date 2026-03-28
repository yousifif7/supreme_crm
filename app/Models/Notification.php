<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use BelongsToAdmin;

    protected $fillable = [
        'admin_id',
        'user_id',
        'employee_id',
        'type',
        'title',
        'message',
        'read',
        'data',
        'action_url'
    ];

    /**
     * When a notification is created without admin_id (e.g. from API controllers),
     * attempt to derive it automatically from the related ShiftDate via action_url.
     * This means guards booking on/off, accepting/declining shifts, etc. will
     * automatically tag the notification with the owning admin — no API call-site
     * changes needed.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $notification) {
            if (!is_null($notification->admin_id)) {
                return; // already explicitly set
            }

            // Parse a shift-date ID from action_url (e.g. /shift-dates/123/view)
            if ($notification->action_url && preg_match('#/shift-dates/(\d+)#', $notification->action_url, $m)) {
                $shiftDate = ShiftDate::withoutGlobalScope('admin_scope')->select('admin_id')->find((int) $m[1]);
                if ($shiftDate) {
                    $notification->admin_id = $shiftDate->admin_id;
                }
            }
        });
    }

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
