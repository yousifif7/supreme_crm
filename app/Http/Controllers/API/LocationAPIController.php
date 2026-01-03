<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\Patrol;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;


class LocationAPIController extends Controller
{
    //
    public function update(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric',
            'timestamp' => 'date',
            'on_duty' => 'required|boolean',
            'shiftdate_id' => 'nullable',
            'patrol_id' => 'nullable',
        ]);

        $location = Location::create([
            'user_id' => Auth::id(),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'],
            'timestamp' => Carbon::now(),
            'on_duty' => $validated['on_duty'],
            'shiftdate_id' => $validated['shiftdate_id'],
            'patrol_id' => $validated['patrol_id'],
        ]);

        return response()->json(['status' => 'success', 'location_id' => $location->id]);
    }

    public function history(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'shift_id' => 'nullable|string', // Handle shifts if needed
        ]);

        $locations = Location::where('user_id', Auth::id())
            ->whereBetween('timestamp', [$request->date_from, $request->date_to])
            ->orderBy('timestamp', 'asc')
            ->get(['latitude', 'longitude', 'timestamp', 'accuracy']);

        return response()->json([
            'locations' => $locations
        ]);
    }

    public function locations(Patrol $patrol, Request $request)
    {
        $shiftDateId = $request->query('shiftDateId');

        if (!$shiftDateId) {
            return response()->json([
                'error' => 'shiftDateId is required'
            ], 400);
        }

        $locations = Location::where('patrol_id', $patrol->id)
            ->where('shiftdate_id', $shiftDateId)
            ->orderBy('created_at') // optional: order by timestamp
            ->get(['latitude', 'longitude', 'created_at']);

        return response()->json([
            'locations' => $locations
        ]);
    }

    public function disabled(Request $request)
    {
        $user = Auth::user();

        // Throttle notifications so admins don't get spammed
        $cacheKey = "location_disabled:{$user->id}";
        if (Cache::has($cacheKey)) {
            return response()->json(['status' => 'ok', 'message' => 'Notification already sent recently.']);
        }

        $displayName = trim(($user->first_name ?? $user->name) . ' ' . ($user->last_name ?? ''));
        $message = 'Location services disabled by ' . $displayName . ' at: ' . now();

        // Use your existing helper exactly as you showed
        \Notify::toDashboard(
            null,
            'alert',
            'Location Services Disabled',
            $message,
            ""
        );

        // keep cooldown (adjust minutes as you prefer)
        Cache::put($cacheKey, true, now()->addMinutes(30));

        return response()->json(['status' => 'success', 'message' => 'Control has been notified.']);
    }

    public function checkIdle(Request $request)
    {
        $user = $request->user();

        // Get last location
        $lastLocation = Location::where('user_id', $user->id)
            ->orderByDesc('timestamp')
            ->first();

        if (!$lastLocation) {
            return response()->json([
                'message' => 'No location recorded yet',
                'idle_status' => 'unknown'
            ]);
        }

        $now = now(); // current time
        $diffMinutes = $lastLocation->timestamp->diffInMinutes($now); // positive number

        $alerts = [];

        // 15-min idle -> notify guard
        if ($diffMinutes >= 15 && $diffMinutes < 30) {
            send_push_notification(
                $user->id,
                'Idle Alert',
                'You have been idle for 15 minutes.',
                ['type' => 'alert']
            );
            $alerts[] = 'guard_notified';
        }

        // 30-min idle -> notify control
        if ($diffMinutes >= 30) {
            Notify::toDashboard(
                null,
                'alert',
                'Idle Guard Alert',
                'Guard ' . $user->first_name.' '.$user->last_name .' has been idle for 30 minutes.',
                ""
            );
            $alerts[] = 'control_notified';
        }

        return response()->json([
            'idle_minutes' => $diffMinutes,
            'alerts_sent' => $alerts
        ]);
    }

}
