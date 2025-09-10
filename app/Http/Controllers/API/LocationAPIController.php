<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Patrol;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


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
}
