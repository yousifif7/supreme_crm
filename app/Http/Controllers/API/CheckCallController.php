<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CheckCallMedia;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CheckCallController extends Controller
{
    // 17. Get Check Call Schedule
    public function getCheckCalls($shift_id)
    {
        $calls = CheckCall::where('shift_id', $shift_id)->get();

        return response()->json([
            'check_calls' => $calls
        ]);
    }

    // 18. Complete Check Call (App-based)
    public function completeCheckCall(Request $request, $id)
    {
        $validated = $request->validate([
            'media_files' => 'array',
            'media_files.*' => 'string',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'notes' => 'nullable|string',
            'timestamp' => 'required|date',
        ]);

        $checkCall = CheckCall::findOrFail($id);

        // Save media
        if ($request->has('media_files')) {
            foreach ($request->media_files as $base64) {
                $filename = 'check_calls/' . uniqid() . '.jpg';
                Storage::put($filename, base64_decode($base64));
                CheckCallMedia::create([
                    'check_call_id' => $checkCall->id,
                    'file_path' => $filename,
                ]);
            }
        }

        $employee = Employee::where('user_id', Auth::id())->first();

        $checkCall->update([
            'status' => 'completed',
            'employee_id' => $employee->id,
        ]);

        Location::create([
            'user_id' => Auth::id(),
            'latitude' => $validated['location']['latitude'],
            'longitude' => $validated['location']['longitude'],
            'accuracy' => 100,
            'on_duty' => 1,
        ]);

        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Checkcall completed',
            'message' => 'Guard ' . $employee->fore_name . ' ' . $employee->sur_name . ' Completed checkcall ' . $checkCall->name,
            'read' => false,
            'action_url' => "/scheduling"
        ]);

        return response()->json(['message' => 'Check call completed']);
    }

    // 19. Complete Check Call (Phone-based)
    public function phoneComplete(Request $request)
    {
        $request->validate([
            'guard_id' => 'required|exists:users,id',
            'phone_number' => 'required|string',
            'timestamp' => 'required|date',
        ]);

        // For demo purposes, just log the call
        // Optionally, you could mark the nearest pending check call as complete
        return response()->json(['message' => 'Phone check call recorded']);
    }

    public function getCheckCallAlarms(Request $request)
    {
        $user = Auth::user();
        $alarms = CheckCall::whereHas('shift', function ($query) use ($user) {
            $query->where('staff_id',  Employee::where('user_id', $user->id)->first());
        })
            ->where('status', 'pending')
            ->where('scheduled_time', '<', now())
            ->get()
            ->map(function ($checkCall) {
                return [
                    'check_call_id' => $checkCall->id,
                    'scheduled_time' => $checkCall->scheduled_time,
                    'overdue_minutes' => now()->diffInMinutes($checkCall->scheduled_time),
                ];
            });

        return response()->json([
            'active_alarms' => $alarms
        ]);
    }

    public function update(Request $request, $id)
    {
        $checkcall = CheckCall::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string',
            'scheduled_time' => 'date',
            'status' => 'in:pending,completed,missed',
        ]);

        $checkcall->update([
            'name' => $request->name,
            'scheduled_time' => $request->scheduled_time,
            'status' => $request->status,
        ]);

        return response()->json(['message' => 'Check call updated successfully']);
    }

    public function destroy($id)
    {
        CheckCall::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function showUserMap($userId)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        return view('map', compact('userId', 'apiKey'));
    }

    public function getLatestLocations($userId)
    {
        $oneHourAgo = Carbon::now()->subHour();

        $locations = Location::where('user_id', $userId)
            ->where('created_at', '>=', $oneHourAgo)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at']);

        return response()->json($locations);
    }
}
