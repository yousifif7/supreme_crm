<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
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
        Log::info('ShiftNotificationController: web trigger started', ['by' => $request->user()?->id ?? null]);

        // Avoid running the heavy missed-shift checks more than once per hour
        $hourKey = 'missed_shifts_checked_' . now()->format('Y-d-mH');
        if (Cache::has($hourKey)) {
            Log::info('ShiftNotificationController: missed/unassigned checks skipped (recent run)');
        } else {
            // --- Missed Book On Notifications ---
            $now = now();
            // only consider shifts from yesterday and after
            $cutoff = $now->copy()->subDay()->toDateString();

            $missedBookOns = ShiftDate::whereNotNull('staff_id')
                ->whereNull('absentee_start_time')
                ->whereDate('shift_date', '>=', $cutoff)
                ->whereDate('shift_date', '<=', $now->toDateString())
                ->whereTime('start_time', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
                ->get();

            foreach ($missedBookOns as $sd) {
                if ($sd->is_assign == 2 && $sd->staff_id) {
                    $perShiftKey = 'missed_shift_on_' . $sd->id;
                    if (Cache::has($perShiftKey)) {
                        continue;
                    }

                    try {
                        $employee = User::find($sd->staff_id);
                        $guardName = $employee ? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) : 'Unknown';

                        // Push to the guard
                        if ($employee) {
                            send_push_notification($employee->id, 'Missed Book On', "You did not book on for your shift at {$sd->start_time} on {$sd->shift_date}.", ['shift_date_id' => $sd->id, 'type' => 'missed_book_on']);
                        }
                    } catch (\Exception $e) {
                        Log::warning('ShiftNotificationController: missed book on notify failed: ' . $e->getMessage());
                    }

                    // Mark this shift as notified for 1 hour to prevent repeats
                    Cache::put($perShiftKey, true, now()->addHour());
                }
            }

            // --- Missed Book Off Notifications ---
            // only consider shifts from yesterday and after
            $missedBookOffs = ShiftDate::whereNotNull('staff_id')
                ->whereNull('absentee_end_time')
                ->whereDate('shift_date', '>=', $cutoff)
                ->whereDate('shift_date', '<=', $now->toDateString())
                ->whereTime('end_time', '<=', $now->copy()->subMinutes(15)->format('H:i:s'))
                ->get();

            foreach ($missedBookOffs as $sd) {
                $perShiftKey = 'missed_shift_off_' . $sd->id;
                if (Cache::has($perShiftKey)) {
                    continue;
                }

                // Only consider missed book-off if the guard actually booked on for this shift
                $bookedOnExists = ShiftBooking::where('shift_id', $sd->id)
                    ->where('type', 'book_on')
                    ->where('user_id', $sd->staff_id)
                    ->exists();

                if (! $bookedOnExists) {
                    // nothing to do for this shift
                    continue;
                }

                try {
                    $employee = User::find($sd->staff_id);
                    $guardName = $employee ? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) : 'Unknown';

                    if ($employee) {
                        send_push_notification($employee->id, 'Missed Book Off', "You did not book off for your shift at {$sd->end_time} on {$sd->shift_date}.", ['shift_date_id' => $sd->id, 'type' => 'missed_book_off']);
                    }
                } catch (\Exception $e) {
                    Log::warning('ShiftNotificationController: missed book off notify failed: ' . $e->getMessage());
                }

                Cache::put($perShiftKey, true, now()->addHour());
            }

            // --- Assigned Shifts Starting Soon and not accepted (push to guard) ---
            $assignedSoon = ShiftDate::whereNotNull('staff_id')
                ->whereDate('shift_date', '=', $now->toDateString())
                ->whereTime('start_time', '>=', $now->format('H:i:s'))
                ->whereTime('start_time', '<=', $now->copy()->addHour()->format('H:i:s'))
                ->get();

            foreach ($assignedSoon as $sd) {
                if ($sd->is_assign !== 2 && $sd->staff_id) {
                    $perShiftKey = 'unaccepted_shift_' . $sd->id;
                    if (Cache::has($perShiftKey)) {
                        continue;
                    }

                    try {
                        $employee = User::find($sd->staff_id);
                        $message = "A shift at {$sd->start_time} on {$sd->shift_date} is starting soon and you have not accepted it.";

                        if ($employee) {
                            send_push_notification($employee->id, 'Unaccepted Shift', $message, ['shift_date_id' => $sd->id, 'type' => 'unaccepted_shift']);
                        }
                    } catch (\Exception $e) {
                        Log::warning('ShiftNotificationController: unaccepted shift notify failed: ' . $e->getMessage());
                    }

                    Cache::put($perShiftKey, true, now()->addHour());
                }
            }

            // Mark overall check as done for this hour
            Cache::put($hourKey, true, now()->addHour());
        }

        $users = User::role('security_staff')->get();
        Log::info('ProcessShiftNotifications (web): found users count', ['count' => $users->count()]);
        $alerts = [];
        $cooldownMinutes = 15; // show alerts for 15 minutes after first shown
        $patrolMarkDelay = 10; // minutes after detection before marking patrol as missed
        $checkcallMarkDelay = 5; // minutes after detection before marking checkcall as missed
        $visibilityMinutes = 5; // keep recent alerts visible for this many minutes

        $processed = 0;

        foreach ($users as $user) {
            // per-user alerts list — reset so alerts don't bleed between users
            $alerts = [];

            // Patrol alerts - get all pending patrols and filter by user
            $allPatrols = Patrol::where('status', 'pending')->get();
            $patrols = $allPatrols->filter(function($patrol) use ($user) {
                $shift = ShiftDate::find($patrol->shift_id);
                return $shift && $shift->staff_id == $user->id && $shift->is_assign == 3;
            });

            Log::info('ProcessShiftNotifications (web): patrols found', ['user_id' => $user->id, 'count' => $patrols->count()]);  

            foreach ($patrols as $patrol) {
                $shift = ShiftDate::find($patrol->shift_id);
                if (!$shift) continue;

                $start = Carbon::parse($patrol->start_time);
                $diff = now()->diffInMinutes($start, false);

                // 5-min warning
                if ($diff <= 5 && $diff > 0) {
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
                                send_push_notification($user->id, 'Upcoming Patrol', "Patrol starting soon: {$patrol->name} at {$patrol->start_time}", ['patrol_id' => $patrol->id, 'type' => 'patrol_warning', 'shift_date_id' => $patrol->shift_id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Upcoming patrol for {$empName}";
                                $adminMessage = "{$empName} has an upcoming patrol '{$patrol->name}' scheduled at {$patrol->start_time}.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for patrol_warning: ' . $e->getMessage());
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                // Patrol is due NOW (at start time)
                if ($diff <= 0 && $diff > -5) {
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
                                send_push_notification($user->id, 'Patrol Due Now', "Your patrol is due now: {$patrol->name}. Please start immediately.", ['patrol_id' => $patrol->id, 'type' => 'patrol_due', 'shift_date_id' => $patrol->shift_id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Patrol due for {$empName}";
                                $adminMessage = "{$empName}'s patrol '{$patrol->name}' is due now at {$patrol->start_time}.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for patrol_due: ' . $e->getMessage());
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                // Patrol is overdue (between 5-45 mins past start time)
                if ($diff <= -5 && $diff > -45) {
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
                                $minutesOverdue = abs((int)$diff);
                                send_push_notification($user->id, 'Patrol Overdue', "Your patrol is {$minutesOverdue} minutes overdue and not completed: {$patrol->name}. Please complete it now.", ['patrol_id' => $patrol->id, 'type' => 'patrol_overdue', 'shift_date_id' => $patrol->shift_id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Overdue patrol for {$empName}";
                                $adminMessage = "{$empName}'s patrol '{$patrol->name}' is {$minutesOverdue} minutes overdue and not completed.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for patrol_overdue: ' . $e->getMessage());
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                // 45-min completion reminder (5 mins before 50-min deadline)
                if ($diff <= -45 && $diff > -50) {
                        $alert = [
                            'type' => 'patrol_completion_reminder',
                            'patrol_id' => $patrol->id,
                            'title' => 'Complete Patrol Soon',
                            'message' => 'Please complete your patrol soon: ' . $patrol->name,
                            'scheduled_time' => $patrol->start_time,
                        ];

                        $cacheKey = "alerts:patrol_completion:user:{$user->id}:patrol:{$patrol->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                send_push_notification($user->id, 'Complete Patrol Soon', "Please complete your patrol soon: {$patrol->name}. You have 5 minutes remaining.", ['patrol_id' => $patrol->id, 'type' => 'patrol_completion_reminder', 'shift_date_id' => $patrol->shift_id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Patrol completion reminder for {$empName}";
                                $adminMessage = "{$empName} has 5 minutes to complete patrol '{$patrol->name}'.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for patrol_completion_reminder: ' . $e->getMessage());
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }

                // 50-min missed
                if ($diff <= -50) {
                        $markerKey = "missed_marker:patrol:user:{$user->id}:patrol:{$patrol->id}";
                        $missedThreshold = Carbon::parse($patrol->start_time)->addMinutes(50);
                        $markAtCarbon = $missedThreshold->copy()->addMinutes($patrolMarkDelay);

                        if (now()->gte($markAtCarbon)) {
                            try {
                                $patrol->update(['status' => 'missed']);
                            } catch (\Exception $e) {
                                Log::error('Failed to mark patrol missed', ['patrol_id' => $patrol->id, 'error' => $e->getMessage()]);
                            }
                            Cache::forget($markerKey);

                            $alertType = 'patrol_missed';
                            $alertMessage = 'You missed a patrol: ' . $patrol->name;
                        } else {
                            if (!Cache::has($markerKey)) {
                                $secondsUntilMark = max(1, $markAtCarbon->diffInSeconds(now()));
                                Cache::put($markerKey, $markAtCarbon->timestamp, now()->addSeconds($secondsUntilMark + 60));
                            }
                            $markAt = Cache::get($markerKey);

                            $alertType = 'patrol_missed_pending';
                            $remaining = $markAt ? max(0, (int)$markAt - now()->timestamp) : ($patrolMarkDelay * 60);
                            $alertMessage = 'Patrol appears missed and will be marked in ' . gmdate('i:s', $remaining) . ' unless handled: ' . $patrol->name;
                        }

                        $alert = [
                            'type' => $alertType,
                            'patrol_id' => $patrol->id,
                            'title' => ($alertType === 'patrol_missed') ? 'Missed Patrol' : 'Potential Missed Patrol',
                            'message' => $alertMessage,
                            'scheduled_time' => $patrol->start_time,
                        ];

                        $cacheKey = "alerts:patrol_missed:user:{$user->id}:patrol:{$patrol->id}";
                        if (!Cache::has($cacheKey)) {
                            Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));
                            $alert['_first_shown'] = true;
                            try {
                                // Send push notification to guard
                                $pushTitle = ($alertType === 'patrol_missed') ? 'Missed Patrol' : 'Potential Missed Patrol';
                                send_push_notification($user->id, $pushTitle, $alertMessage, ['patrol_id' => $patrol->id, 'type' => $alertType, 'shift_date_id' => $patrol->shift_id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = ($alertType === 'patrol_missed') ? "Missed patrol by {$empName}" : "Potential missed patrol for {$empName}";
                                $adminMessage = ($alertType === 'patrol_missed') ? "{$empName} missed patrol '{$patrol->name}'." : "{$empName} appears to have missed patrol '{$patrol->name}' and it will be marked soon unless handled.";
                                $actionUrl = '/shift-dates/' . $patrol->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for patrol_missed: ' . $e->getMessage());
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }
            }

            // Check Calls - get all pending and filter by user
            $allCheckCalls = CheckCall::where('status', 'pending')->get();
            $checkCalls = $allCheckCalls->filter(function($checkCall) use ($user) {
                $shift = $checkCall->shiftDate;
                return $shift && $shift->staff_id == $user->id && $shift->is_assign == 3;
            });

            Log::info('ProcessShiftNotifications (web): checkcalls found', ['user_id' => $user->id, 'count' => $checkCalls->count()]);

            foreach ($checkCalls as $checkCall) {
                // guard: ensure related shiftDate exists
                if (!$checkCall->shiftDate) {
                    continue;
                }
                $scheduled = Carbon::parse($checkCall->scheduled_time);
                $diff = now()->diffInMinutes($scheduled, false);

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
                                send_push_notification($user->id, 'Upcoming Check Call', "Check call coming up: {$checkCall->name} at {$checkCall->scheduled_time}", ['checkcall_id' => $checkCall->id, 'type' => 'checkcall_warning', 'shift_date_id' => $checkCall->shiftDate->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Upcoming check call for {$empName}";
                                $adminMessage = "{$empName} has an upcoming check call '{$checkCall->name}' scheduled at {$checkCall->scheduled_time}.";
                                $actionUrl = '/shift-dates/' . $checkCall->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for checkcall_warning: ' . $e->getMessage());
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
                                send_push_notification($user->id, 'Complete Check Call Soon', "Please complete your check call soon: {$checkCall->name}. You have 5 minutes remaining.", ['checkcall_id' => $checkCall->id, 'type' => 'checkcall_completion_reminder', 'shift_date_id' => $checkCall->shiftDate->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Check call completion reminder for {$empName}";
                                $adminMessage = "{$empName} has 5 minutes to complete check call '{$checkCall->name}'.";
                                $actionUrl = '/shift-dates/' . $checkCall->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for checkcall_completion_reminder: ' . $e->getMessage());
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
                                Log::error('Failed to mark checkcall missed', ['checkcall_id' => $checkCall->id, 'error' => $e->getMessage()]);
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
                                send_push_notification($user->id, $pushTitle, $alertMessage, ['checkcall_id' => $checkCall->id, 'type' => $alertType, 'shift_date_id' => $checkCall->shiftDate->id]);
                                
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = ($alertType === 'checkcall_missed') ? "Missed check call by {$empName}" : "Potential missed check call for {$empName}";
                                $adminMessage = ($alertType === 'checkcall_missed') ? "{$empName} missed check call '{$checkCall->name}'." : "{$empName} appears to have missed check call '{$checkCall->name}' and it will be marked soon unless handled.";
                                $actionUrl = '/shift-dates/' . $checkCall->shiftDate->id.'/view';
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for checkcall_missed: ' . $e->getMessage());
                            }
                        } else {
                            $alert['_first_shown'] = false;
                        }

                    $alerts[] = $alert;
                }
            }

            // Recent alerts handling (same as console command)
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

                    try {
                        Log::info('ProcessShiftNotifications (web): sending push', ['user_id' => $user->id, 'title' => $alert['title'], 'type' => $alert['type']]);
                        send_push_notification($user->id, $alert['title'], $alert['message'], $alert);
                        Log::debug('ProcessShiftNotifications (web): push sent', ['user_id' => $user->id, 'type' => $alert['type']]);
                        $processed++;
                    } catch (\Exception $e) {
                        Log::error('Failed to send push for alert', ['user_id' => $user->id, 'alert' => $alert, 'error' => $e->getMessage()]);
                    }

                    $idx = $recentMap[$uid];
                    $recent[$idx] = array_merge($recent[$idx], $alert);
                    Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));
                }
            }
        }

        Log::info('ShiftNotificationController: web trigger ended', ['processed' => $processed]);

        return response()->json(['success' => true, 'processed' => $processed]);
    }
}
