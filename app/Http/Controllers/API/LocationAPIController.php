<?php

namespace App\Http\Controllers\API;

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
            'timestamp' => 'required|date',
            'on_duty' => 'required|boolean',
        ]);

        $location = Location::create([
            'user_id' => Auth::id(),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'],
            'timestamp' => $validated['timestamp'],
            'on_duty' => $validated['on_duty'],
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
}
