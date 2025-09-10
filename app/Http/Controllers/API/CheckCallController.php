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
            'timestamp' => 'nullable|date',
        ]);

        $checkCall = CheckCall::findOrFail($id);
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'No employee linked to this user.'], 404);
        }
        $now = Carbon::now(); // incoming timestamp assumed UTC
        $scheduledUtc = Carbon::parse($checkCall->scheduled_time, 'UTC'); // stored in DB as UTC

        $earliest = $scheduledUtc->copy()->subMinutes(5);
        $latest   = $scheduledUtc->copy()->addMinutes(15);


        if ($now->lt($earliest)) {
            return response()->json([
                'message' => 'Too early! Check call can only be completed 5 minutes before its due time. '
                    . $scheduledUtc->format('Y-m-d H:i') . " (UTC). Your local time: " . $now,
            ], 422);
        }

        if ($now->gt($latest)) {
            $checkCall->status = 'missed';
            $checkCall->save();
            return response()->json([
                'message' => 'Missed! Check call can only be completed within 15 minutes after its due time. '
                    . $scheduledUtc->format('Y-m-d H:i') . " (UTC). Your local time: " . $now,
            ], 422);
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

            // --- Add watermark / text overlay ---
            $imgPath = public_path($filePath);
            $img = null;

            if (in_array(pathinfo($imgPath, PATHINFO_EXTENSION), ['jpg', 'jpeg'])) {
                $img = imagecreatefromjpeg($imgPath);
            } elseif (pathinfo($imgPath, PATHINFO_EXTENSION) === 'png') {
                $img = imagecreatefrompng($imgPath);
            }

            if ($img) {
                $white = imagecolorallocate($img, 255, 255, 255);
                $blackTrans = imagecolorallocatealpha($img, 0, 0, 0, 80); // semi-transparent bg


                $shiftdate = ShiftDate::find($checkCall->shift_id);

                // Compose text
                $text = "Time: " . date('Y-m-d H:i', strtotime($data['timestamp'])) .
                    "\nEmployee: " . $employee->fore_name . ' ' . $employee->sur_name .
                    "\nLat: " . $data['location']['latitude'] . "  " .
                    "Lng: " . $data['location']['longitude'] .
                    "\nSite: " . $shiftdate->shift->site->site_name .
                    "\nLocation: " . $shiftdate->shift->site->address;

                $lines = explode("\n", $text);
                $lineHeight = 15; // spacing per line
                $padding = 5;
                $x = $padding;
                $y = $padding;

                // Draw semi-transparent rectangle behind text
                $rectHeight = count($lines) * $lineHeight + 2 * $padding;
                $rectWidth = 300; // approximate width
                imagefilledrectangle($img, 0, 0, $rectWidth, $rectHeight, $blackTrans);

                // Draw white text
                foreach ($lines as $line) {
                    imagestring($img, 5, $x, $y, $line, $white);
                    $y += $lineHeight;
                }

                // Save image back
                if (in_array(pathinfo($imgPath, PATHINFO_EXTENSION), ['jpg', 'jpeg'])) {
                    imagejpeg($img, $imgPath, 90);
                } else {
                    imagepng($img, $imgPath);
                }

                imagedestroy($img);
            }

            // Save to DB
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

            send_push_notification(
                $user->id,
                'Checkcall completed',
                'You have Completed your checkcall.',
                ['checkcall' => $checkCall]
            );
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

        send_push_notification(
            $checkcall->employee_id,
            'Checkcall updated',
            'An admin has updated your checkcall! check on your app now.',
            ['checkcall' => $checkcall],
        );

        return response()->json(['message' => 'Check call updated successfully']);
    }

    public function destroy($id)
    {
        CheckCall::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
