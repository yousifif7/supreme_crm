<?php

namespace App\Console\Commands;

use Notify;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Shift;
use App\Models\Patrol;
use App\Models\Employee;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Helpers;

class ProcessShiftNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:process-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send shift notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        Log::info('Notification command started');

        // Also invoke the web controller process to run any logic present there
        try {
            $controllerClass = '\\App\\Http\\Controllers\\ShiftNotificationController';
            if (class_exists($controllerClass)) {
                Log::info('Invoking ShiftNotificationController::process from scheduled command');
                $controller = new $controllerClass();
                $controller->process(new \Illuminate\Http\Request());
            } else {
                Log::warning('ShiftNotificationController class not found; skipping controller invocation');
            }
        } catch (\Throwable $e) {
            Log::error('Failed invoking ShiftNotificationController::process: ' . $e->getMessage());
        }

        $users = User::role('security_staff')->get();
        Log::info('ProcessShiftNotifications: found users count', ['count' => $users->count()]);
        $cooldownMinutes = 15; // show alerts for 15 minutes after first shown
        $patrolMarkDelay = 10; // minutes after detection before marking patrol as missed
        $checkcallMarkDelay = 5; // minutes after detection before marking checkcall as missed
        $visibilityMinutes = 5; // keep recent alerts visible for this many minutes

        foreach($users as $user){
        $alerts = [];
        /**
         * 2. Patrol Alerts (5 min notification / 50 min missed)
         */
        $allPatrols = Patrol::where('status', 'pending')->get();
        $patrols = $allPatrols->filter(function($patrol) use ($user) {
            $shift = ShiftDate::find($patrol->shift_id);
            return $shift && $shift->staff_id == $user->id && $shift->is_assign == 2;
        });

        Log::info('ProcessShiftNotifications: patrols found', ['user_id' => $user->id, 'count' => $patrols->count()]);

        foreach ($patrols as $patrol) {
            // debug: log each patrol found for this user
            $shift = ShiftDate::find($patrol->shift_id);

            if (!$shift) continue;

            $start = Carbon::parse($patrol->start_time);
            $diff = now()->diffInMinutes($start, false); // negative if past

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
                        // Dashboard notify for admin
                        try {
                            $emp = $user->employee;
                            $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                            $adminTitle = "Upcoming patrol for {$empName}";
                            $adminMessage = "{$empName} has an upcoming patrol '{$patrol->name}' scheduled at {$patrol->start_time}.";
                            $actionUrl = '/patrols/' . $patrol->id;
                            Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                        } catch (\Exception $e) {
                            Log::warning('Dashboard notify failed for patrol_warning: ' . $e->getMessage());
                        }
                    } else {
                        $alert['_first_shown'] = false;
                    }

                $alerts[] = $alert;
            }

            // 50-min missed (compute canonical mark time from start_time + threshold + delay)
            if ($diff <= -50) {
                    $markerKey = "missed_marker:patrol:user:{$user->id}:patrol:{$patrol->id}";

                    // canonical mark time = patrol start + 50 minutes (threshold) + patrolMarkDelay
                    $missedThreshold = Carbon::parse($patrol->start_time)->addMinutes(50);
                    $markAtCarbon = $missedThreshold->copy()->addMinutes($patrolMarkDelay);

                    // If mark time already passed, mark immediately
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
                        // ensure a persistent marker exists until after the mark time
                        if (!Cache::has($markerKey)) {
                            $secondsUntilMark = max(1, $markAtCarbon->diffInSeconds(now()));
                            // keep marker a bit longer than the mark time so the next request can detect it
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
                        // Dashboard notify for admin
                        try {
                            $emp = $user->employee;
                            $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                            $adminTitle = ($alertType === 'patrol_missed') ? "Missed patrol by {$empName}" : "Potential missed patrol for {$empName}";
                            $adminMessage = ($alertType === 'patrol_missed') ? "{$empName} missed patrol '{$patrol->name}'." : "{$empName} appears to have missed patrol '{$patrol->name}' and it will be marked soon unless handled.";
                            $actionUrl = '/patrols/' . $patrol->id;
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

        /**
         * 3. Check Call Alerts (5 min notification / 15 min missed)
         */
        $allCheckCalls = CheckCall::where('status', 'pending')->get();
        $checkCalls = $allCheckCalls->filter(function($checkCall) use ($user) {
            $shift = $checkCall->shiftDate;
            return $shift && $shift->staff_id == $user->id && $shift->is_assign == 2;
        });

        Log::info('ProcessShiftNotifications: checkcalls found', ['user_id' => $user->id, 'count' => $checkCalls->count()]);

        foreach ($checkCalls as $checkCall) {
            if(!$checkCall->shiftDate) {
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
                            // Dashboard notify for admin
                            try {
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = "Upcoming check call for {$empName}";
                                $adminMessage = "{$empName} has an upcoming check call '{$checkCall->name}' scheduled at {$checkCall->scheduled_time}.";
                                $actionUrl = '/checkcalls/' . $checkCall->id;
                                Notify::toDashboard(1, 'alert', $adminTitle, $adminMessage, $actionUrl);
                            } catch (\Exception $e) {
                                Log::warning('Dashboard notify failed for checkcall_warning: ' . $e->getMessage());
                            }
                    } else {
                        $alert['_first_shown'] = false;
                    }
    
                $alerts[] = $alert;
            }

            // 15-min missed (compute canonical mark time from scheduled_time + threshold + delay)
            if ($diff <= -15) {
                    $markerKey = "missed_marker:checkcall:user:{$user->id}:checkcall:{$checkCall->id}";
    
                    // canonical mark time = scheduled time + 15 minutes (threshold) + checkcallMarkDelay
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
                            // Dashboard notify for admin
                            try {
                                $emp = $user->employee;
                                $empName = $emp ? trim(($emp->fore_name ?? '') . ' ' . ($emp->sur_name ?? '')) : ($user->first_name ?? ($user->name ?? 'Employee'));
                                $adminTitle = ($alertType === 'checkcall_missed') ? "Missed check call by {$empName}" : "Potential missed check call for {$empName}";
                                $adminMessage = ($alertType === 'checkcall_missed') ? "{$empName} missed check call '{$checkCall->name}'." : "{$empName} appears to have missed check call '{$checkCall->name}' and it will be marked soon unless handled.";
                                $actionUrl = '/checkcalls/' . $checkCall->id;
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

        // Recent-alerts cache: keep last few alerts visible for $visibilityMinutes
        $recentKey = "recent_alerts:user:{$user->id}";
        $recent = Cache::get($recentKey, []);

        // Build a lookup of recent UIDs
        $recentMap = [];
        foreach ($recent as $idx => $r) {
            if (!empty($r['_uid'])) {
                $recentMap[$r['_uid']] = $idx;
            }
        }

        // For each newly computed alert, add to recent cache if not present
        foreach ($alerts as $alert) {
            // compute a stable uid for this alert
            $idPart = $alert['type'] . ':' . (
                $alert['patrol_id'] ?? $alert['checkcall_id'] ?? ($alert['scheduled_time'] ?? uniqid())
            );
            $uid = md5($idPart);
            $alert['_uid'] = $uid;

            if (!isset($recentMap[$uid])) {
                // new alert — prepend so newest are first
                array_unshift($recent, $alert);
                // limit stored alerts
                if (count($recent) > 50) {
                    array_pop($recent);
                }
                // update lookup
                $recentMap[$uid] = 0;

                // persist recent list for visibility window
                Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));

                // send push for new items only
                try {
                    Log::info('ProcessShiftNotifications: sending push', ['user_id' => $user->id, 'title' => $alert['title'], 'type' => $alert['type']]);
                    send_push_notification(
                        $user->id,
                        $alert['title'],
                        $alert['message'],
                        $alert
                    );
                    Log::debug('ProcessShiftNotifications: push sent', ['user_id' => $user->id, 'type' => $alert['type']]);
                } catch (\Exception $e) {
                    Log::error('Failed to send push for alert', ['user_id' => $user->id, 'alert' => $alert, 'error' => $e->getMessage()]);
                }
            } else {
                // existing: update the stored alert content in case message changed
                $idx = $recentMap[$uid];
                $recent[$idx] = array_merge($recent[$idx], $alert);
                Cache::put($recentKey, $recent, now()->addMinutes($visibilityMinutes));
            }
        }

        // Return the recent list (newest first), strip internal uid keys
        $result = [];
        foreach ($recent as $r) {
            if (isset($r['_uid'])) {
                unset($r['_uid']);
            }
            if (isset($r['_first_shown'])) {
                unset($r['_first_shown']);
            }
            $result[] = $r;
        }
    }


        Log::info('Notification command Ended');

        // Close any lingering database connections
        \DB::disconnect();

        $this->info('✅ Shift notifications processed successfully.');
    }
}