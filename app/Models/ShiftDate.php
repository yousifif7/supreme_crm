<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftDate extends Model
{
    use SoftDeletes, LogsChanges;
    protected $table = 'shift_dates';
    protected  $fillable = ['staff_id', 'shift_id', 'shift_date', 'start_time', 'end_time', 'total_hours', 'break_time', 'absentee_end', 'absentee_start_time', 'absentee_end_time', 'is_assign',' status'];


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

     public function checkCalls()
    {
        return $this->hasMany(CheckCall::class,'shift_id');
    }
     public function patrols()
    {
        return $this->hasMany(Patrol::class,'shift_id');
    }

}
