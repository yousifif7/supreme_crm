<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Patrol;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use App\Models\ShiftBooking;
use Notify;

class ShiftNotificationController extends BaseController
{
    public function process(Request $request)
    {
        

        // Run missed/unassigned checks every minute (no hourly guard) so reminders and auto book-off fire promptly.
        $now = now();
        // only consider shifts from yesterday and after
        $cutoff = $now->copy()->subDay()->toDateString();

        // --- Auto-complete in_progress patrols after 50 minutes ---
        // Query patrols that are in_progress and have a related shift within the last 24 hours,
        // then compute full start datetimes in PHP to decide completion (handles overnight correctly).
        $inProgressPatrols = Patrol::where('status', 'in_progress')
            ->with('shift')
            ->whereHas('shift', function ($q) use ($cutoff, $now) {
                $q->whereBetween('shift_date', [$cutoff, $now->toDateString()]);
            })->get();

        foreach ($inProgressPatrols as $patrol) {
            try {
                // Parse patrol start_time. If it already contains a date, parse directly.
                try {
                    $rawStart = trim((string)$patrol->start_time);
                    if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]/', $rawStart)) {
                        $startDt = Carbon::parse($rawStart);
                    } elseif (!empty($patrol->shift) && !empty($patrol->shift->shift_date)) {
                        $startDt = Carbon::parse($patrol->shift->shift_date . ' ' . $rawStart);
                    } else {
                        $startDt = Carbon::parse(now()->toDateString() . ' ' . $rawStart);
                    }
                } catch (\Exception $e) {
                    continue;
                }

                // minutes since patrol start (positive if start in past)
                $minutesSinceStart = $startDt->diffInMinutes($now, false);

                

                if ($minutesSinceStart >= 50) {
                    $patrol->status = 'completed';
                    $patrol->completed_at = $now;
                    $patrol->save();

                    

                    // Notify the guard (use eager-loaded relation when available)
                    $shiftDate = $patrol->shift ?? null;
                    if ($shiftDate && $shiftDate->staff_id) {
                        send_push_notification(
                            $shiftDate->staff_id,
                            'Patrol Auto-Completed',
                            "Your patrol '{$patrol->name}' has been automatically marked as completed.",
                            ['type' => 'patrol', 'patrolId' => $patrol->id]
                        );
                    }
                }
            } catch (\Exception $e) {
            }
        }

        // Consider only shifts within the last 24 hours (cutoff → today)
        $missedBookOns = ShiftDate::whereNotNull('staff_id')
            ->whereNull('absentee_start_time')
            ->whereBetween('shift_date', [$cutoff, $now->toDateString()])
            ->whereTime('start_time', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->select('id', 'staff_id', 'start_time', 'shift_date', 'is_assign')
            ->get();

        // --- Auto Book Off (5 minutes after shift end) ---
        $threshold = now()->subMinutes(5)->format('Y-m-d H:i:s');

// Select shifts where the scheduled end has passed the threshold AND
// either `absentee_end_time` is NULL OR the recorded absentee time is earlier
// than the scheduled end (admin may have accidentally set an early absentee time).
// Handle overnight shifts by resolving times to proper datetimes via CASE.
$missedBookOffs = ShiftDate::whereNotNull('staff_id')
    ->whereBetween('shift_date', [$cutoff, $now->toDateString()])
    ->whereRaw("(
        -- scheduled end datetime <= threshold
        CASE
            WHEN end_time <= start_time THEN TIMESTAMP(DATE_ADD(shift_date, INTERVAL 1 DAY), end_time)
            ELSE TIMESTAMP(shift_date, end_time)
        END <= ?
    ) AND (
        -- absentee_end_time is NULL OR absentee datetime < scheduled end datetime
        absentee_end_time IS NULL OR (
            CASE
                WHEN absentee_end_time <= start_time THEN TIMESTAMP(DATE_ADD(shift_date, INTERVAL 1 DAY), absentee_end_time)
                ELSE TIMESTAMP(shift_date, absentee_end_time)
            END < (
                CASE
                    WHEN end_time <= start_time THEN TIMESTAMP(DATE_ADD(shift_date, INTERVAL 1 DAY), end_time)
                    ELSE TIMESTAMP(shift_date, end_time)
                END
            )
        )
    )", [$threshold])
    ->select('id', 'staff_id', 'start_time', 'end_time', 'shift_date', 'absentee_end_time', 'is_assign')
    ->get();

 

// --- Auto book-off: apply admin-like book_off for eligible shifts ---
foreach ($missedBookOffs as $mb) {
    try {
        // $mb already contains selected ShiftDate fields; avoid extra lookup
        $sd = $mb;
        if (!$sd) continue;

        // Only auto book-off if currently booked ON (is_assign == 3)
        if ($sd->staff_id && $sd->is_assign == 3) {
            $sd->absentee_end_time = now()->format('H:i:s');
            $sd->status = 'booked_off';
            $sd->is_assign = 4;
            $sd->save();

            $latestBooking = ShiftBooking::where('user_id', $sd->staff_id)
                ->where('type', 'book_on')
                ->latest('created_at')
                ->first();

            if ($latestBooking) {
                $latestBooking->type = 'book_off';
                $latestBooking->timestamp = now();
                $latestBooking->save();
            } else {
            }

            // Notify staff
            send_push_notification(
                $sd->staff_id,
                'Shift Booked Off',
                "You have been automatically booked OFF for shift (ID: {$sd->id}) that ended at {$sd->end_time}",
                ['type' => 'shift', 'shiftId' => $sd->id]
            );

            
        }
        } catch (\Exception $e) {
    }
}

        // --- Book On Reminder (5 minutes before shift start) ---
        $bookOnReminders5MinsBefore = ShiftDate::whereNotNull('staff_id')
            ->where('is_assign', 2) // Accepted shifts
            ->whereNull('absentee_start_time')
            ->whereDate('shift_date', $now->toDateString())
            ->whereTime('start_time', '<=', $now->copy()->addMinutes(5)->format('H:i:s'))
            ->whereTime('start_time', '>', $now->format('H:i:s'))
            ->select('id', 'staff_id', 'start_time', 'shift_date')
            ->get();

        // --- Book On Reminder (5 minutes after shift start - missed book on) ---
        $bookOnReminders5MinsAfter = ShiftDate::whereNotNull('staff_id')
            ->where('is_assign', 2) // Accepted shifts
            ->whereNull('absentee_start_time')
            ->whereBetween('shift_date', [$cutoff, $now->toDateString()])
            ->whereTime('start_time', '<=', $now->copy()->subMinutes(5)->format('H:i:s'))
            ->whereTime('start_time', '>', $now->copy()->subMinutes(15)->format('H:i:s'))
            ->select('id', 'staff_id', 'start_time', 'shift_date')
            ->get();

        // collect staff ids to eager-load once (include ALL reminder queries)
        $staffIds = collect($missedBookOns)
            ->pluck('staff_id')
            ->merge(collect($missedBookOffs)->pluck('staff_id'))
            ->merge(collect($bookOnReminders5MinsBefore)->pluck('staff_id'))
            ->merge(collect($bookOnReminders5MinsAfter)->pluck('staff_id'))
            ->unique()
            ->filter()
            ->values()
            ->all();
        
        $staffMap = [];
        if (!empty($staffIds)) {
            $staffMap = User::whereIn('id', $staffIds)
                ->select('id', 'first_name', 'last_name')
                ->get()
                ->keyBy('id');
        }

        foreach ($bookOnReminders5MinsBefore as $sd) {
            $perShiftKey = 'bookon_reminder_5before_' . $sd->id;
            if (Cache::has($perShiftKey)) {
                continue;
            }

            // Use prefetched staff map; skip per-row lookup to avoid extra DB queries
            $employee = $staffMap[$sd->staff_id] ?? null;
            if ($employee) {
                try {
                    send_push_notification(
                        $employee->id,
                        'Shift Starting Soon',
                        "Your shift starts at {$sd->start_time}. Please prepare to book on.",
                        ['type' => 'shift', 'shiftId' => $sd->id]
                    );
                } catch (\Exception $e) {
                    
                }
            }

            Cache::put($perShiftKey, true, now()->addMinutes(10));
        }

        foreach ($bookOnReminders5MinsAfter as $sd) {
            $perShiftKey = 'bookon_reminder_5after_' . $sd->id;
            if (Cache::has($perShiftKey)) {
                continue;
            }

            // Use prefetched staff map; skip per-row lookup to avoid extra DB queries
            $employee = $staffMap[$sd->staff_id] ?? null;
            if ($employee) {
                try {
                    send_push_notification(
                        $employee->id,
                        'Missed Book On',
                        "You have not booked on for your shift that started at {$sd->start_time}. Please book on now.",
                        ['type' => 'shift', 'shiftId' => $sd->id]
                    );
                } catch (\Exception $e) {
                    
                }
            }

            Cache::put($perShiftKey, true, now()->addMinutes(15));
        }

        $users = User::role('security_staff')->get();
        
        $alerts = [];
        $cooldownMinutes = 15; // show alerts for 15 minutes after first shown
        $patrolMarkDelay = 10; // minutes after detection before marking patrol as missed
        $checkcallMarkDelay = 5; // minutes after detection before marking checkcall as missed
        $visibilityMinutes = 5; // keep recent alerts visible for this many minutes

        // Load all pending patrols and checkcalls ONCE with their shifts (eager loading)
        // Only check items within last 24 hours to keep processing efficient
        $allPatrols = Patrol::where('status', 'pending')
            ->where('start_time', '>=', now()->subDay())
            ->with('shift')
            ->whereHas('shift') // Only get patrols with existing shifts
            ->get();
        // --- Mark pending patrols as missed if not started within 15 minutes ---
        foreach ($allPatrols as $p) {
            try {
                $start = Carbon::parse($p->start_time);
                $graceEnd = $start->copy()->addMinutes(15);
                if (now()->gt($graceEnd) && $p->status === 'pending') {
                    $p->status = 'missed';
                    $p->save();

                    // Notify admin and guard (if assigned)
                    try {
                        if($patrol->shift->shift->is_assign == 3){
                            Notify::toDashboard(1, 'alert', 'Patrol missed', "Patrol '{$p->name}' (ID: {$p->id}) was not started within 15 minutes and has been marked missed.", '/shift-dates/' . ($p->shift?->id ?? $p->shift_id) . '/view');
                        }
                    } catch (\Exception $e) {
                        
                    }

                    if ($p->shift && $p->shift->staff_id) {
                        try {
                            send_push_notification($p->shift->staff_id, 'Patrol Missed', "Your patrol '{$p->name}' was marked as missed.", ['type' => 'patrol', 'patrolId' => $p->id]);
                        } catch (\Exception $e) {
                        
                        }
                    }
                }
            } catch (\Exception $e) {
                
            }
        }
        $allCheckCalls = CheckCall::where('status', 'pending')
            ->where('scheduled_time', '>=', now()->subDay())
            ->with('shiftDate')
            ->whereHas('shiftDate') // Only get checkcalls with existing shifts
            ->get();
        

        $processed = 0;

        foreach ($users as $user) {
            // per-user alerts list — reset so alerts don't bleed between users
            $alerts = [];

            // Patrol alerts - filter by user from already-loaded patrols
            $patrols = $allPatrols->filter(function($patrol) use ($user) {
                // Use eager-loaded relationship instead of fresh query
                $shift = $patrol->shift;
                
                if (!$shift) {
                    return false;
                }
                
                if ($shift->staff_id != $user->id) {
                    return false;
                }
                
                $now = now();
                $shiftStart = Carbon::parse($shift->shift_date . ' ' . $shift->start_time);
                $shiftEnd = Carbon::parse($shift->shift_date . ' ' . $shift->end_time);
                
                // Handle overnight shifts
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }
                
                // Send notifications if shift is booked on (is_assign == 3) and within shift window
                if ($shift->is_assign == 3) {
                    return $now->gte($shiftStart->copy()->subMinutes(15)) && $now->lte($shiftEnd);
                }
                
                return false;
            });

            foreach ($patrols as $patrol) {
                // Use eager-loaded relationship
                $shift = $patrol->shift;
                if (!$shift) continue;
                $start = Carbon::parse($patrol->start_time);
                // minutes until start: positive => start in future, negative => minutes since start
                $minutesUntilStart = now()->diffInMinutes($start, false);

                // 5-min warning (start in future within 5 minutes)
                if ($minutesUntilStart <= 5 && $minutesUntilStart > 0) {
                        $alert = [
                            'type' => 'patrol_warning',
                            'patrol_id' => $patrol->id,
                            'title' => 'Upcoming Patrol',
                            'message' => 'Patrol starting soon: ' . $patrol->name,
                            'scheduled_time' => $patrol->start_time,
                        ];

                        $cacheKey = "alerts:patrol_warning:user:{$user->id}:patrol:{$patrol->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                send_push_notification($user->id, 'Upcoming Patrol', "Patrol starting soon: {$patrol->name} at {$patrol->start_time}", ['type' => 'patrol', 'patrolId' => $patrol->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Upcoming patrol for {$empName}";
                                $adminMessage = "{$empName} has an upcoming patrol '{$patrol->name}' scheduled at {$patrol->start_time}.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                // Patrol is due NOW (at start time)
                if ($minutesUntilStart <= 0 && $minutesUntilStart > -5) {
                        $alert = [
                            'type' => 'patrol_due',
                            'patrol_id' => $patrol->id,
                            'title' => 'Patrol Due Now',
                            'message' => 'Your patrol is due now: ' . $patrol->name,
                            'scheduled_time' => $patrol->start_time,
                        ];

                        $cacheKey = "alerts:patrol_due:user:{$user->id}:patrol:{$patrol->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                send_push_notification($user->id, 'Patrol Due Now', "Your patrol is due now: {$patrol->name}. Please start immediately.", ['type' => 'patrol', 'patrolId' => $patrol->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Patrol due for {$empName}";
                                $adminMessage = "{$empName}'s patrol '{$patrol->name}' is due now at {$patrol->start_time}.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }


                // Patrol is overdue (between 5-15 mins past start time)
                if ($minutesUntilStart <= -5 && $minutesUntilStart > -15) {
                    $alert = [
                        'type' => 'patrol_overdue',
                        'patrol_id' => $patrol->id,
                        'title' => 'Patrol Overdue',
                        'message' => 'Your patrol is overdue and not completed: ' . $patrol->name,
                        'scheduled_time' => $patrol->start_time,
                    ];

                        $cacheKey = "alerts:patrol_overdue:user:{$user->id}:patrol:{$patrol->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                $minutesOverdue = abs((int)$minutesUntilStart);
                                send_push_notification($user->id, 'Patrol Overdue', "Your patrol is {$minutesOverdue} minutes overdue and not completed: {$patrol->name}. Please complete it now.", ['type' => 'patrol', 'patrolId' => $patrol->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Overdue patrol for {$empName}";
                                $adminMessage = "{$empName}'s patrol '{$patrol->name}' is {$minutesOverdue} minutes overdue and not completed.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }
                // Patrol is overdue by 15+ minutes (missed)
                if ($minutesUntilStart <= -15) {
                    $alert = [
                        'type' => 'patrol_missed',
                        'patrol_id' => $patrol->id,
                        'title' => 'Patrol Missed',
                        'message' => 'Your patrol is overdue and marked as missed: ' . $patrol->name,
                        'scheduled_time' => $patrol->start_time,
                    ];

                    $cacheKey = "alerts:patrol_missed:user:{$user->id}:patrol:{$patrol->id}";
                    if (!Cache::has($cacheKey)) {
                        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                        $alert['_first_shown'] = true;
                        try {
                            // Only send push if patrol wasn't already globally marked missed earlier
                            if ($patrol->status !== 'missed') {
                                $minutesOverdue = abs((int)$minutesUntilStart);
                                send_push_notification($user->id, 'Patrol Missed', "Your patrol is {$minutesOverdue} minutes overdue: {$patrol->name}.", ['type' => 'patrol', 'patrolId' => $patrol->id]);
                            }

                            $emp = $user->employee;
                            $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                            $adminTitle = "Overdue patrol for {$empName}";
                            $adminMessage = "{$empName}'s patrol '{$patrol->name}' is overdue and not completed.";
                            $actionUrl = '/shift-dates/' . ($patrol->shift->id ?? $patrol->shift_id) . '/view';
                            Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                        } catch (\Exception $e) {
                            
                        }
                    } else {
                        $alert['_first_shown'] = false;
                    }

                    $alerts[] = $alert;
                }

            }

            // Check Calls - filter by user from already-loaded checkcalls
            $checkCalls = $allCheckCalls->filter(function($checkCall) use ($user) {
                $shift = $checkCall->shiftDate;
                
                if (!$shift) {
                    return false;
                }
                
                if ($shift->staff_id != $user->id) {
                    return false;
                }
                
                return $shift->is_assign == 3;
            });

            foreach ($checkCalls as $checkCall) {
                // guard: ensure related shiftDate exists and has scheduled time
                if (!$checkCall->shiftDate || empty($checkCall->scheduled_time)) {
                    continue;
                }
                
                $scheduled = Carbon::parse($checkCall->scheduled_time);
                $diff = now()->diffInMinutes($scheduled, false);

                // 5-min warning
                if ($diff <= 5 && $diff > 0) {
                        $alert = [
                            'type' => 'checkcall_warning',
                            'checkcall_id' => $checkCall->id,
                            'title' => 'Upcoming Check Call',
                            'message' => 'Check call coming up: ' . $checkCall->name,
                            'scheduled_time' => $checkCall->scheduled_time,
                        ];

                        $cacheKey = "alerts:checkcall_warning:user:{$user->id}:checkcall:{$checkCall->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                send_push_notification($user->id, 'Upcoming Check Call', "Check call coming up: {$checkCall->name} at {$checkCall->scheduled_time}", ['type' => 'check-call', 'checkCallId' => $checkCall->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Upcoming check call for {$empName}";
                                $adminMessage = "{$empName} has an upcoming check call '{$checkCall->name}' scheduled at {$checkCall->scheduled_time}.";
                                $actionUrl = '/shift-dates/' . $checkCall->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                // 10-min completion reminder (5 mins before 15-min deadline)
                if ($diff <= -10 && $diff > -15) {
                        $alert = [
                            'type' => 'checkcall_completion_reminder',
                            'checkcall_id' => $checkCall->id,
                            'title' => 'Complete Check Call Soon',
                            'message' => 'Please complete your check call soon: ' . $checkCall->name,
                            'scheduled_time' => $checkCall->scheduled_time,
                        ];

                        $cacheKey = "alerts:checkcall_completion:user:{$user->id}:checkcall:{$checkCall->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                send_push_notification($user->id, 'Complete Check Call Soon', "Please complete your check call soon: {$checkCall->name}. You have 5 minutes remaining.", ['type' => 'check-call', 'checkCallId' => $checkCall->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Check call completion reminder for {$empName}";
                                $adminMessage = "{$empName} has 5 minutes to complete check call '{$checkCall->name}'.";
                                $actionUrl = '/shift-dates/' . $checkCall->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                if ($diff <= -15) {
                        $markerKey = "missed_marker:checkcall:user:{$user->id}:checkcall:{$checkCall->id}";
                        $missedThreshold = Carbon::parse($checkCall->scheduled_time)->addMinutes(15);
                        $markAtCarbon = $missedThreshold->copy()->addMinutes($checkcallMarkDelay);

                        if (now()->gte($markAtCarbon)) {
                            try {
                                $checkCall->update(['status' => 'missed']);
                            } catch (\Exception $e) {
                                
                            }
                            Cache::forget($markerKey);

                            $alertType = 'checkcall_missed';
                            $alertMessage = 'You missed a check call: ' . $checkCall->name;
                        } else {
                            if (!Cache::has($markerKey)) {
                                $secondsUntilMark = max(1, $markAtCarbon->diffInSeconds(now()));
                                Cache::put($markerKey, $markAtCarbon->timestamp, now()->addSeconds($secondsUntilMark + 60));
                            }
                            $markAt = Cache::get($markerKey);

                            $alertType = 'checkcall_missed_pending';
                            $remaining = $markAt ? max(0, (int)$markAt - now()->timestamp) : ($checkcallMarkDelay * 60);
                            $alertMessage = 'Check call appears missed and will be marked in ' . gmdate('i:s', $remaining) . ' unless handled: ' . $checkCall->name;
                        }

                        $alert = [
                            'type' => $alertType,
                            'checkcall_id' => $checkCall->id,
                            'title' => ($alertType === 'checkcall_missed') ? 'Missed Check Call' : 'Potential Missed Check Call',
                            'message' => $alertMessage,
                            'scheduled_time' => $checkCall->scheduled_time,
                        ];

                        $cacheKey = "alerts:checkcall_missed:user:{$user->id}:checkcall:{$checkCall->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                $pushTitle = ($alertType === 'checkcall_missed') ? 'Missed Check Call' : 'Potential Missed Check Call';
                                send_push_notification($user->id, $pushTitle, $alertMessage, ['type' => 'check-call', 'checkCallId' => $checkCall->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = ($alertType === 'checkcall_missed') ? "Missed check call by {$empName}" : "Potential missed check call for {$empName}";
                                $adminMessage = ($alertType === 'checkcall_missed') ? "{$empName} missed check call '{$checkCall->name}'." : "{$empName} appears to have missed check call '{$checkCall->name}' and it will be marked soon unless handled.";
                                $actionUrl = '/shift-dates/' . $checkCall->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }
            }

            $recentKey = "recent_alerts:user:{$user->id}";
            $recent = Cache::get($recentKey, []);

            $recentMap = [];
            foreach ($recent as $idx => $r) {
                if (!empty($r['_uid'])) {
                    $recentMap[$r['_uid']] = $idx;
                }
            }

            foreach ($alerts as $alert) {
                $idPart = $alert['type'] . ':' . (
                    $alert['document_id'] ?? $alert['patrol_id'] ?? $alert['checkcall_id'] ?? ($alert['scheduled_time'] ?? uniqid())
                );
                $uid = md5($idPart);
                $alert['_uid'] = $uid;

                if (!isset($recentMap[$uid])) {
                    array_unshift($recent, $alert);
                    if (count($recent) > 50) array_pop($recent);
                    $recentMap[$uid] = 0;
                    Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));

                    // Push notifications are already sent above when _first_shown is true
                    // Only count processed alerts
                    if (!empty($alert['_first_shown'])) {
                        $processed++;
                    }

                    $idx = $recentMap[$uid];
                    $recent[$idx] = array_merge($recent[$idx], $alert);
                    Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));
                }
            }
        }

        

        return response()->json(['success' => true, 'processed' => $processed]);
    }
}
