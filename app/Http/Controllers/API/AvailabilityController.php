<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Availability;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $guardId = Auth::id(); // or passed guard_id if admin

        $availability = Availability::where('user_id', $guardId)
            ->orderBy('day_of_week')
            ->get(['day_of_week', 'start_time as start', 'end_time as end']);

        return response()->json([
            'availability' => $availability
        ]);
    }

    public function update(Request $request)
    {
        $guardId = Auth::id();

        $request->validate([
            'availability' => 'required|array|min:1|max:7',
            'availability.*.dayOfWeek' => 'required|integer|min:0|max:6',
            'availability.*.start' => 'required|string|date_format:H:i',
            'availability.*.end'   => 'required|string|date_format:H:i',
        ]);

        // Convert input to keyed array (dayOfWeek => data)
        $inputAvail = collect($request->availability)
            ->keyBy('dayOfWeek');

        // Loop through all 7 days (0 = Sunday … 6 = Saturday)
        foreach (range(0, 6) as $dayOfWeek) {
            if ($inputAvail->has($dayOfWeek)) {
                // Use provided availability
                $day = $inputAvail[$dayOfWeek];
                $start = $day['start'];
                $end   = $day['end'];

                // Special case: 00:00 - 00:00 → unavailable
                if ($start === "00:00" && $end === "00:00") {
                    $start = null;
                    $end   = null;
                } elseif (
                    Carbon::createFromFormat('H:i', $end)
                    ->lessThanOrEqualTo(Carbon::createFromFormat('H:i', $start))
                ) {
                    return response()->json([
                        'message' => "End time must be after start time for day {$dayOfWeek}"
                    ], 422);
                }
            } else {
                // Not provided → set default (unavailable)
                $start = null;
                $end   = null;
            }

            Availability::updateOrCreate(
                [
                    'user_id' => $guardId,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'start_time' => $start,
                    'end_time'   => $end,
                ]
            );
        }

        $staff = User::find(Auth::id());
        
            Notification::create([
                'user_id' => 1,
                'employee_id' => null,
                'type' => 'alert',
                'title' => 'Availability hours updated',
                'message' => "{$staff->first_name} {$staff->last_name} Updated his Availabilty Hours.",
                'read' => false,
                'action_url' => 'employees#'.$staff->employee->id,
        ]);

            Notification::create([
                'user_id' => Auth::id(),
                'employee_id' => Auth::id(),
                'type' => 'alert',
                'title' => 'Availability hours updated',
                'message' => 'You have updated your Availabilty hours succssfully.',
                'read' => false,
            ]);

        return response()->json(['message' => 'Availability updated successfully']);
    }
}
