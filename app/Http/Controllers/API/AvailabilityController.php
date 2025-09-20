<?php

namespace App\Http\Controllers\API;

use App\Models\Availability;
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
            'availability' => 'required|array|size:7',
            'availability.*.dayOfWeek' => 'required|integer|min:0|max:6',
            'availability.*.start' => 'required|date_format:H:i',
            'availability.*.end' => 'required|date_format:H:i|after:availability.*.start',
        ]);

        foreach ($request->availability as $day) {
            Availability::updateOrCreate(
                [
                    'user_id' => $guardId,
                    'day_of_week' => $day['dayOfWeek']
                ],
                [
                    'start_time' => $day['start'],
                    'end_time' => $day['end']
                ]
            );
        }

        return response()->json(['message' => 'Availability updated successfully']);
    }
}
