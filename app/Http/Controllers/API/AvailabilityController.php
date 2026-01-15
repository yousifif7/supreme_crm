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
            ->get(['day_of_week', 'start_time', 'end_time'])
            ->map(function ($a) {
                $start = $a->start_time;
                $end = $a->end_time;
                $period = 'Custom';

                // Map known ranges to Day / Night
                if ($start === '07:00' && $end === '19:00') {
                    $period = 'Day';
                } elseif ($start === '19:00' && $end === '07:00') {
                    $period = 'Night';
                }

                return [
                    'day_of_week' => $a->day_of_week,
                    'period' => $period,
                    'start' => $start,
                    'end' => $end,
                ];
            });

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
            // period: Day | Night only — optional; if omitted the day will be left null
            'availability.*.period' => 'nullable|string|in:Day,Night',
        ]);

        // Convert input to keyed array (dayOfWeek => data)
        $inputAvail = collect($request->availability)
            ->keyBy('dayOfWeek');

        // Loop through all 7 days (0 = Sunday … 6 = Saturday)
        foreach (range(0, 6) as $dayOfWeek) {
            if ($inputAvail->has($dayOfWeek)) {
                // Use provided availability
                $day = $inputAvail[$dayOfWeek];
                $period = $day['period'] ?? null;

                if ($period === 'Day') {
                    $start = '07:00';
                    $end = '19:00';
                } elseif ($period === 'Night') {
                    // Overnight: start 19:00 → end 07:00 (next day)
                    $start = '19:00';
                    $end = '07:00';
                } else {
                    // No period provided -> leave unavailable (null)
                    $start = null;
                    $end = null;
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

        return response()->json(['message' => 'Availability updated successfully']);
    }
}
