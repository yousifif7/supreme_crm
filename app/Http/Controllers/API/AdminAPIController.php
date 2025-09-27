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
use App\Models\CheckCall;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
        $documentTypes = [
            'sia_licence_file',
            'passport_file',
            'proof_of_address_file',
            'ni_letter_file',
            'first_aid_certificate_file',
            'act_certificate_file',
            'driving_licence_file',
        ];

        // 1. Document Expiry Alerts (all users)
        $users = User::with('documents')->get();
        foreach ($users as $user) {
            foreach ($documentTypes as $type) {
                $doc = $user->documents()
                    ->where('document_type', $type)
                    ->orderByDesc('expiry_date')
                    ->first();

                if ($doc && $doc->expiry_date) {
                    $expiryDate = Carbon::parse($doc->expiry_date);

                    if ($expiryDate->isFuture() && $expiryDate->lte(now()->addDays(30))) {
                        $daysRemaining = now()->diffInDays($expiryDate);
                        $alerts[] = [
                            'type' => 'document_expiry',
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'document_id' => $doc->id,
                            'title' => 'Document Expiry Alert',
                            'message' => "{$user->name}'s {$doc->document_type} will expire in {$daysRemaining} day(s) on {$doc->expiry_date}.",
                            'expiry_date' => $doc->expiry_date,
                            'days_remaining' => $daysRemaining,
                        ];
                    }
                }
            }
        }

        // 2. Patrol Alerts
        $patrols = Patrol::with('shift.staff')->where('status', 'pending')->get();
        foreach ($patrols as $patrol) {
            $start = Carbon::parse($patrol->start_time);
            $diff = now()->diffInMinutes($start, false);

            if ($diff <= 5 && $diff > 0) {
                $alerts[] = [
                    'type' => 'patrol_warning',
                    'user_id' => $patrol->shift->staff_id ?? 'N/A',
                    'user_name' => $patrol->shift->user->name ?? '',
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
                    'message' => "{$patrol->shift->staff?->name} missed patrol: {$patrol->name}",
                    'scheduled_time' => $patrol->start_time,
                ];
            }
        }

        // 3. Check Call Alerts
        $checkCalls = CheckCall::with('shiftDate.staff')->where('status', 'pending')->get();
        foreach ($checkCalls as $checkCall) {
            $scheduled = Carbon::parse($checkCall->scheduled_time);
            $diff = now()->diffInMinutes($scheduled, false);

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
                    'message' => " missed check call: {$checkCall->name}",
                    'scheduled_time' => $checkCall->scheduled_time,
                ];
            }
        }

        $now = Carbon::now();
        $twentyFourHoursLater = $now->copy()->addDay();

        $sites = Site::with(['shifts.shiftDates' => function ($query) use ($now, $twentyFourHoursLater) {
            $query->whereNull('staff_id')
                ->where('is_assign',0)
                ->whereBetween('start_time', [$now, $twentyFourHoursLater]); // within 24 hours
        }])->get();

        foreach ($sites as $site) {
            foreach ($site->shifts as $shift) {
                foreach ($shift->shiftDates as $shiftDate) {
                    $alerts[] = [
                        'type' => 'shift_unassigned',
                        'shift_date_id' => $shiftDate->id,
                        'shift_id' => $shift->id,
                        'site_name' => $site->site_name,
                        'title' => 'Unassigned Shift',
                        'message' => "Shift at {$site->site_name} is unassigned and starts within 24 hours.",
                        'scheduled_time' => $shiftDate->start_time,
                    ];
                }
            }
        }

        // 5. Panic Button Pressed
        // $panicLogs = PanicLog::with('user')->where('resolved', false)->get();
        // foreach ($panicLogs as $panic) {
        //     $alerts[] = [
        //         'type' => 'panic_button',
        //         'user_id' => $panic->user_id,
        //         'user_name' => $panic->user->name ?? '',
        //         'title' => 'Panic Alert',
        //         'message' => "{$panic->user->name} triggered the panic button at {$panic->created_at->format('H:i:s d-m-Y')}.",
        //         'time' => $panic->created_at,
        //     ];
        // }

        return response()->json(['alerts' => $alerts]);
    }
}
