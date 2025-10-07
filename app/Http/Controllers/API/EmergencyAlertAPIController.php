<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\EmergencyAlert;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class EmergencyAlertAPIController extends Controller
{
    public function trigger(Request $request)
    {
        $validated = $request->validate([
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.address' => 'required|string',
            'enable_device_alarm' => 'required|boolean',
            'message' => 'nullable|string',
            'timestamp' => 'date',
        ]);

        $alert = EmergencyAlert::create([
            'user_id' => Auth::id(),
            'latitude' => $validated['location']['latitude'],
            'longitude' => $validated['location']['longitude'],
            'address' => $validated['location']['address'],
            'enable_device_alarm' => $validated['enable_device_alarm'],
            'message' => 'Emergency: ' . Auth::user()->first_name . ' ' . Auth::user()->last_name . ' Activated panic Alarm',
            'timestamp' => Carbon::now(),
            'acknowledged_by_control' => false
        ]);

        send_push_notification(
            Auth::id(),
            'Emergency alert',
            'Energmency alert has been triggered from your device!',
            ['alert' => $alert]
        );

        Notify::toDashboard(
            null,
            'alert',
            'Panic button',
            'Emergency alert button activated by ' . Auth::user()->first_name . ' ' . Auth::user()->last_name . ' At ' . $alert->timestamp,
            ""
        );

        return response()->json([
            'alert_id' => $alert->id,
            'acknowledged_by_control' => $alert->acknowledged_by_control
        ]);
    }

    public function cancel(Request $request, EmergencyAlert $alert)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        if ($alert->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($alert->cancelled) {
            return response()->json(['message' => 'Alert already cancelled'], 400);
        }

        $alert->update([
            'cancelled' => true,
            'cancel_reason' => $request->reason
        ]);

        return response()->json(['message' => 'Emergency alert cancelled']);
    }

    public function acknowledge($id)
    {
        $alert = EmergencyAlert::findOrFail($id);

        if ($alert->acknowledged_by_control) {
            return response()->json([
                'success' => false,
                'message' => 'Alert already acknowledged'
            ], 400);
        }

        $alert->update(['acknowledged_by_control' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully',
            'alert_id' => $alert->id
        ]);
    }
}
