<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Site;
use App\Models\User;
use App\Models\Alarm;
use App\Models\Alert;
use App\Models\Shift;
use App\Models\Patrol;
use App\Models\Message;
use App\Models\DobEntry;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use Illuminate\Http\Request;
use App\Models\EmergencyAlert;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AdminAPIController extends Controller
{
    // 48. Admin - Override Missed Alarms
    public function overrideMissedAlarm(Request $request, $alarm_id)
    {
        $request->validate([
            'reason' => 'required|string',
            'resolved' => 'required|boolean',
        ]);

        $alarm = Alarm::findOrFail($alarm_id);

        $alarm->override_reason = $request->reason; // Make sure this column exists in DB
        $alarm->resolved = $request->resolved;
        $alarm->save();

        return response()->json(['message' => 'Alarm override updated successfully']);
    }

    public function deleteMessage(Request $request, $message_id)
    {
        // Validate body input
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $message = Message::find($message_id);
        if (!$message) {
            return response()->json(['error' => 'Message not found.'], 404);
        }

        // Here you can log the reason somewhere, for example in a separate audit table or logs (optional)
        // For now, just delete the message
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
            'reason' => $request->reason
        ]);
    }

    // 50. Admin - Edit DOB Entry
    public function editDOBEntry(Request $request, $entry_id)
    {
        // Validate body input
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'edit_reason' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $dobEntry = DobEntry::find($entry_id);
        if (!$dobEntry) {
            return response()->json(['error' => 'DOB entry not found.'], 404);
        }

        // Optionally log the edit_reason somewhere (audit logs, history table)
        // For now just update the entry
        $dobEntry->title = $request->title;
        $dobEntry->description = $request->description;
        $dobEntry->save();

        return response()->json([
            'success' => true,
            'message' => 'DOB entry updated successfully.',
            'edit_reason' => $request->edit_reason,
            'dob_entry' => $dobEntry
        ]);
    }

    public function sendAlertToGuard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guard_id' => 'required|string|exists:alarms,id', // changed guards to alarms
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'trigger_alarm' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Assuming alarms is the table for guards or similar entity
        $alarm = Alert::find($request->guard_id);

        if (!$alarm) {
            return response()->json(['error' => 'Alarm not found'], 404);
        }

        // Create alert - assuming alerts table/model exists
        $alert = Alert::create([
            'guard_id' => $request->guard_id,
            'message' => $request->message,
            'priority' => $request->priority,
            'trigger_alarm' => $request->trigger_alarm,
            'sent_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alert sent to guard successfully',
            'alert_id' => $alert
        ]);
    }

    public function dashboardAlerts(Request $request)
    {
        $alerts = [];

        // --- 1. Emergency Alerts ---
        $emergencyAlerts = EmergencyAlert::where('acknowledged_by_control', false)
            ->with('user') // assuming EmergencyAlert has a relation to User
            ->get();

        foreach ($emergencyAlerts as $alert) {
            $user = User::find($alert->user_id);
            $alerts[] = [
                'type' => 'panic_button',
                'alert_id' => $alert->id,
                'user_id' => $alert->user_id,
                'user_name' => $user?->first_name . ' ' . $user?->last_name ?? 'Unknown',
                'latitude' => $alert->latitude,
                'longitude' => $alert->longitude,
                'address' => $alert->address,
                'message' => $alert->message,
                'enable_device_alarm' => $alert->enable_device_alarm,
                'title' => 'Emergency Alert',
                'timestamp' => $alert->timestamp,
            ];
        }

        // --- 2. Patrol Alerts ---
        $patrols = Patrol::where('status', 'pending')
            ->whereHas('shift', function ($q) {
                $q->whereNotNull('staff_id');
            })
            ->with('shift.staff')
            ->get();

        foreach ($patrols as $patrol) {
            $start = Carbon::parse($patrol->start_time);
            $diff = now()->diffInMinutes($start, false);
            $userName = $patrol->shiftDate?->staff?->first_name ?? 'Unknown';

            if ($diff <= 5 && $diff > 0) {
                $alerts[] = [
                    'type' => 'patrol_warning',
                    'user_id' => $patrol->shift->staff_id ?? 'N/A',
                    'user_name' => $patrol->shift->user->first_name ?? '',
                    'patrol_id' => $patrol->id,
                    'shift_id' => $patrol->shift_id,
                    'title' => 'Upcoming Patrol',
                    'message' => "Patrol starting soon: {$patrol->name}",
                    'scheduled_time' => $patrol->start_time,
                ];
            }

            if ($diff <= -50) {
                $patrol->update(['status' => 'missed']);
                $alerts[] = [
                    'type' => 'patrol_missed',
                    'user_id' => $patrol?->shift?->staff_id ?? 'N/A',
                    'user_name' => $patrol->shift->staff->name ?? '',
                    'patrol_id' => $patrol->id,
                    'title' => 'Missed Patrol',
                    'message' => "Staff {$userName} missed patrol: {$patrol->name}",
                    'scheduled_time' => $patrol->start_time,
                ];
            }
        }

        // --- 3. Check Call Alerts ---
        $checkCalls = CheckCall::where('status', 'pending')
            ->whereHas('shiftDate', function ($q) {
                $q->whereNotNull('staff_id');
            })
            ->with('shiftDate.staff')
            ->get();

        foreach ($checkCalls as $checkCall) {
            $scheduled = Carbon::parse($checkCall->scheduled_time);
            $diff = now()->diffInMinutes($scheduled, false);

            $userName = $checkCall->shiftDate?->staff?->first_name ?? 'Unknown';

            if ($diff <= 5 && $diff > 0) {
                $alerts[] = [
                    'type' => 'checkcall_warning',
                    'user_id' => $checkCall->shiftDate->staff_id ?? 'N/A',
                    'user_name' => $checkCall->shiftDate->staff->name ?? '',
                    'checkcall_id' => $checkCall->id,
                    'shift_id' => $checkCall->shift_id,
                    'title' => 'Upcoming Check Call',
                    'message' => "Check call coming up: {$checkCall->name}",
                    'scheduled_time' => $checkCall->scheduled_time,
                ];
            }

            if ($diff <= -15) {
                $checkCall->update(['status' => 'missed']);
                $alerts[] = [
                    'type' => 'checkcall_missed',
                    'user_id' => $checkCall->shiftDate->staff_id ?? 0,
                    'user_name' => $checkCall->shiftDate->user->name ?? '',
                    'checkcall_id' => $checkCall->id,
                    'shift_id' => $checkCall->shift_id,
                    'title' => 'Missed Check Call',
                    'message' => "Staff {$userName} missed check call: {$checkCall->name}",
                    'scheduled_time' => $checkCall->scheduled_time,
                ];
            }
        }

        // --- 4. Unassigned Shifts ---
        $now = Carbon::now();
        $twentyFourHoursLater = $now->copy()->addDay();

        $shiftDates = ShiftDate::whereNull('staff_id')
            ->whereRaw("TIMESTAMP(shift_date, start_time) >= ?", [$now])
            ->whereRaw("TIMESTAMP(shift_date, start_time) <= ?", [$twentyFourHoursLater])
            ->get();

        foreach ($shiftDates as $sd) {
            if ($sd->shift && $sd->shift->site) {
                $alerts[] = [
                    'type' => 'shift_unassigned',
                    'shift_date_id' => $sd->id,
                    'shift_id' => $sd->shift->id,
                    'site_name' => $sd->shift->site->site_name,
                    'title' => 'Unassigned Shift',
                    'message' => "Shift at {$sd->shift->site->site_name} is unassigned and starts within 24 hours.",
                    'scheduled_time' => $sd->start_time,
                ];
            }
        }

        // --- 5. Idle Alerts ---
        $now = Carbon::now();
        $activeUsers = User::whereHas('locations')->get();

        foreach ($activeUsers as $user) {
            // Get the latest shift booking for this user
            $latestBooking = \App\Models\ShiftBooking::where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            // Only proceed if user is on-duty
            if (!$latestBooking || $latestBooking->type !== 'book_on') {
                continue;
            }

            $lastLocation = \App\Models\Location::where('user_id', $user->id)
                ->orderByDesc('timestamp')
                ->first();

            if ($lastLocation) {
                $diffMinutes = $lastLocation->timestamp->diffInMinutes($now);

                $cacheKey = "idle_alert_sent_user_{$user->id}";
                if ($diffMinutes >= 30) {
                    // Only send alert if not cached
                    if (!Cache::has($cacheKey)) {
                        $alerts[] = [
                            'type' => 'idle_control',
                            'user_id' => $user->id,
                            'user_name' => $user->first_name . ' ' . $user->last_name,
                            'title' => 'Idle Guard Alert',
                            'message' => "Guard {$user->first_name} {$user->last_name} has been idle for 30 minutes.",
                            'last_seen' => $lastLocation->timestamp,
                        ];

                        // Cache for 1 hour (or until user moves)
                        Cache::put($cacheKey, true, now()->addHour());
                    }
                } else {
                    // If user is active again, clear cache
                    Cache::forget($cacheKey);
                }
            }
        }
        if (Auth::user()->hasRole('')) {
            $alerts=$alerts;
        } else{
            $alerts=[];
        }

        return response()->json(['alerts' => $alerts]);
    }
}
