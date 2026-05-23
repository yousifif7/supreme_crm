<?php

namespace App\Models;

use App\Traits\LogsChanges;
use App\Traits\BelongsToAdmin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftDate extends Model
{
    use SoftDeletes, LogsChanges, BelongsToAdmin;
    protected $table = 'shift_dates';
    protected  $fillable = ['admin_id', 'staff_id', 'shift_id', 'training_id', 'shift_date', 'start_time', 'end_time', 'total_hours', 'break_time', 'absentee_end', 'absentee_start_time', 'absentee_end_time', 'is_assign', 'status', 'invoiced', 'invoice_id', 'require_media', 'guard_rate', 'site_rate', 'subcontractor_id'];


    const STATUS_PENDING       = 0;
    const STATUS_DISPATCHED    = 1;
    const STATUS_ACCEPTED      = 2;
    const STATUS_STARTED       = 3;
    const STATUS_ENDED         = 4;
    const STATUS_REJECTED      = 5;
    const STATUS_CANCELLED     = 6;
    const STATUS_PRE_START     = 7;
    const STATUS_AWAIT_FINISH  = 8;



    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING      => 'Pending',
            self::STATUS_DISPATCHED   => 'Dispatched',
            self::STATUS_ACCEPTED     => 'Accepted',
            self::STATUS_STARTED      => 'Started',
            self::STATUS_ENDED        => 'Ended',
            self::STATUS_REJECTED     => 'Rejected',
            self::STATUS_CANCELLED    => 'Cancelled',
            self::STATUS_PRE_START    => 'Pre-start',
            self::STATUS_AWAIT_FINISH => 'Await-finish',
        ];
    }

    // Optional: Status Badge HTML Map
    public static function getStatusBadge($status)
    {
        $badgeMap = [
            self::STATUS_PENDING      => '<span class="badge bg-secondary">Pending</span>',
            self::STATUS_DISPATCHED   => '<span class="badge bg-info">Dispatched</span>',
            self::STATUS_ACCEPTED     => '<span class="badge bg-primary">Accepted</span>',
            self::STATUS_STARTED      => '<span class="badge bg-success">Started</span>',
            self::STATUS_ENDED        => '<span class="badge bg-dark">Ended</span>',
            self::STATUS_REJECTED     => '<span class="badge bg-danger">Rejected</span>',
            self::STATUS_CANCELLED    => '<span class="badge bg-warning">Cancelled</span>',
            self::STATUS_PRE_START    => '<span class="badge bg-secondary">Pre-start</span>',
            self::STATUS_AWAIT_FINISH => '<span class="badge bg-light text-dark">Await-finish</span>',
        ];

        return $badgeMap[$status] ?? '<span class="badge bg-secondary">Pending</span>';
    }


    public function shift()
    {
        return $this->belongsTo(Shift::class)->with('site');
    }
    
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function note()
    {
        return $this->hasMany(ShiftNote::class, 'shift_date_id');
    }

    public function checkCalls()
    {
        return $this->hasMany(CheckCall::class, 'shift_id');
    }

    public function bookings()
    {
        return $this->hasMany(ShiftBooking::class, 'shift_id');
    }

    public function patrols()
    {
        return $this->hasMany(Patrol::class, 'shift_id');
    }

    public function trainings()
    {
        return $this->belongsToMany(
            \App\Models\TrainingMaterial::class,
            'shift_trainings',
            'shift_date_id',
            'training_id'
        )->withTimestamps();
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'shiftdate_id');
    }

    public function subcontractor()
    {
        return $this->belongsTo(Subcontractor::class, 'subcontractor_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Minutes the guard was late booking on (actual absentee_start_time vs scheduled start_time).
     * Returns 0 when on-time or early; null when either time is missing.
     */
    public function getBookOnLateMinutesAttribute(): ?int
    {
        if (empty($this->absentee_start_time) || empty($this->start_time)) {
            return null;
        }
        try {
            $scheduled = Carbon::parse($this->start_time);
            $actual = Carbon::parse($this->absentee_start_time);
            if ($actual->lessThanOrEqualTo($scheduled)) {
                return 0;
            }
            return (int) $scheduled->diffInMinutes($actual);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Minutes the guard booked off early (actual absentee_end_time before scheduled end_time).
     * Returns 0 when on-time or late; null when either time is missing.
     */
    public function getBookOffEarlyMinutesAttribute(): ?int
    {
        if (empty($this->absentee_end_time) || empty($this->end_time)) {
            return null;
        }
        try {
            $scheduled = Carbon::parse($this->end_time);
            $actual = Carbon::parse($this->absentee_end_time);
            if ($actual->greaterThanOrEqualTo($scheduled)) {
                return 0;
            }
            return (int) $actual->diffInMinutes($scheduled);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Human-readable book-on lateness (e.g. "1 hour 15 mins"). Null when not late or unknown.
     */
    public function getBookOnLateDisplayAttribute(): ?string
    {
        return $this->formatDurationMinutes($this->book_on_late_minutes);
    }

    /**
     * Human-readable book-off earliness (e.g. "1 hour 15 mins"). Null when not early or unknown.
     */
    public function getBookOffEarlyDisplayAttribute(): ?string
    {
        return $this->formatDurationMinutes($this->book_off_early_minutes);
    }

    /**
     * Format a minute count as "X hour(s) Y min(s)" with correct singular/plural.
     * Returns null for null/zero so callers can skip rendering altogether.
     */
    protected function formatDurationMinutes(?int $mins): ?string
    {
        if ($mins === null || $mins <= 0) {
            return null;
        }

        $hours = intdiv($mins, 60);
        $remaining = $mins % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        if ($remaining > 0) {
            $parts[] = $remaining . ' ' . ($remaining === 1 ? 'min' : 'mins');
        }

        return implode(' ', $parts);
    }
}
