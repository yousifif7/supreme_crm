<?php

namespace App\Http\Controllers\API;

use App\Models\Alarm;
use App\Models\Alert;
use App\Models\Message;
use App\Models\DobEntry;
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
}
