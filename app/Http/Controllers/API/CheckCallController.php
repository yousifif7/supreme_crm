<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CheckCallMedia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Validated;
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
    $data = $request->validate([
        'media_files' => 'nullable|array', // files or base64
        'location.latitude' => 'required|numeric',
        'location.longitude' => 'required|numeric',
        'notes' => 'nullable|string',
        'timestamp' => 'required|date',
    ]);

    $checkCall = CheckCall::findOrFail($id);
    $user = Auth::user();
    $employee = Employee::where('user_id', $user->id)->first();

    if (!$employee) {
        return response()->json(['message' => 'No employee linked to this user.'], 404);
    }

    // Handle media files
    foreach ($data['media_files'] ?? [] as $file) {
        $filePath = null;

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('check_calls'), $filename);
            $filePath = 'check_calls/' . $filename;
        } elseif (is_string($file) && preg_match('/^data:/', $file)) {
            $fileData = preg_replace('/^data:\w+\/\w+;base64,/', '', $file);
            $extension = 'png';
            if (preg_match('/^data:(\w+\/\w+);base64,/', $file, $matches)) {
                $mime = $matches[1];
                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'video/mp4' => 'mp4',
                    'video/avi' => 'avi',
                    'application/pdf' => 'pdf'
                ];
                $extension = $extMap[$mime] ?? 'png';
            }
            if (!file_exists(public_path('check_calls'))) {
                mkdir(public_path('check_calls'), 0755, true);
            }
            $filename = time() . '_' . uniqid() . '.' . $extension;
            file_put_contents(public_path('check_calls/' . $filename), base64_decode($fileData));
            $filePath = 'check_calls/' . $filename;
        } else {
            continue;
        }

        CheckCallMedia::create([
            'check_call_id' => $checkCall->id,
            'file_path' => $filePath,
        ]);
    }

    // Update check call
    $checkCall->update([
        'status' => 'completed',
        'employee_id' => $user->id,
        'notes' => $data['notes'] ?? null,
        'completed_at' => $data['timestamp'],
    ]);

    // Store location
    Location::create([
        'user_id' => $user->id,
        'latitude' => $data['location']['latitude'],
        'longitude' => $data['location']['longitude'],
        'accuracy' => 100,
        'on_duty' => 1,
        'shiftdate_id' => $checkCall->shift_id,
    ]);

    // Notifications (like store)
    try {
        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Checkcall completed',
            'message' => 'Guard ' . $employee->fore_name . ' ' . $employee->sur_name . ' completed checkcall ' . $checkCall->name,
            'read' => false,
            'action_url' => "/shift-dates/{$checkCall->shift_id}/view"
        ]);

        Notification::create([
            'user_id' => null,
            'employee_id' => $employee->id,
            'type' => 'alert',
            'title' => 'Checkcall completed',
            'message' => 'You have completed your check call successfully',
        ]);
    } catch (\Exception $e) {
        \Log::error('Notification failed: ' . $e->getMessage());
    }

    return response()->json([
        'message' => 'Check call completed successfully',
        'check_call_id' => $checkCall->id
    ], 200);
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
}
