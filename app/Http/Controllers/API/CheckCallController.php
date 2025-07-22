<?php
namespace App\Http\Controllers\API;

use App\Models\CheckCall;
use App\Models\CheckCallMedia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
        $request->validate([
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
                    'media_path' => $filename,
                ]);
            }
        }

        $checkCall->update([
            'status' => 'completed',
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
                $query->where('staff_id', $user->id);
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

}
