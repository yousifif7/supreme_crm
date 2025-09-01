<?php

namespace App\Http\Controllers\API;

use Notify;
use App\Models\DobEntry;
use App\Models\DobMedia;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DobApiController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'required|exists:shift_dates,id',
            'entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
            'title' => 'required|string',
            'description' => 'required|string',
            'media_files' => 'nullable|array',
            'media_files.*' => 'nullable', // file upload or base64
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'No employee linked to this user.'], 404);
        }

        // Create DOB entry
        $entry = DobEntry::create([
            'user_id' => $user->id,
            'shift_id' => $data['shift_id'],
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'timestamp' => $data['timestamp'],
        ]);

        // Handle media files (like completeCheckCall)
        foreach ($data['media_files'] ?? [] as $file) {
            $filePath = null;

            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('dob_media'), $filename);
                $filePath = 'dob_media/' . $filename;
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

                if (!file_exists(public_path('dob_media'))) {
                    mkdir(public_path('dob_media'), 0755, true);
                }

                $filename = time() . '_' . uniqid() . '.' . $extension;
                file_put_contents(public_path('dob_media/' . $filename), base64_decode($fileData));
                $filePath = 'dob_media/' . $filename;
            } else {
                continue;
            }

            DobMedia::create([
                'dob_entry_id' => $entry->id,
                'file_url' => $filePath,
            ]);
        }

        // Send basic notification (optional, no logs)
        Notification::create([
            'user_id' => Auth::id(),
            'employee_id' => $employee->id,
            'type' => 'alert',
            'title' => 'DOB Uploaded',
            'message' => 'You have uploaded a DOB entry successfully',
        ]);

        send_push_notification(
            $employee->user_id,
            'DOB uploaded',
            'You have submitted a DOB succesffully.',
            ['entry' => $entry],
        );


        return response()->json([
            'entry_id' => $entry->id,
            'message' => 'DOB entry created successfully',
        ], 201);
    }

    public function index(Request $req)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        $q = DobEntry::with('media')
            ->where('user_id', $employee->user_id);

        if ($req->filled('shift_id')) {
            $q->where('shift_id', $req->shift_id);
        }
        if ($req->filled('date_from')) {
            $q->where('timestamp', '>=', $req->date_from);
        }
        if ($req->filled('date_to')) {
            $q->where('timestamp', '<=', $req->date_to);
        }

        $entries = $q->paginate($req->query('limit', 10));

        return response()->json([
            'entries' => $entries->map(fn($e) => [
                'id' => $e->id,
                'shift_id' => $e->shift_id,
                'entry_type' => $e->entry_type,
                'title' => $e->title,
                'description' => $e->description,
                'media_urls' => $e->media->pluck('file_url'),
                'location' => json_decode($e->location),
                'timestamp' => $e->timestamp,
                'admin_comments' => $e->admin_comments,
                'edit_requested' => $e->edit_requested,
                'created_at' => $e->created_at,
                'updated_at' => $e->updated_at,
            ]),
            'pagination' => [
                'current_page' => $entries->currentPage(),
                'total_pages' => $entries->lastPage(),
                'total' => $entries->total(),
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'shift_id' => 'required|exists:shift_dates,id',
            'entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
            'title' => 'required|string',
            'description' => 'required|string',
            'media_files' => 'nullable|array', // files or base64
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'No employee linked to this user.'], 404);
        }

        $entry = DobEntry::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Update entry
        $entry->update([
            'shift_id' => $data['shift_id'],
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'timestamp' => $data['timestamp'],
        ]);

        // Handle media files
        foreach ($data['media_files'] ?? [] as $file) {
            $filePath = null;

            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('dob_media'), $fileName);
                $filePath = 'dob_media/' . $fileName;
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
                if (!file_exists(public_path('dob_media'))) {
                    mkdir(public_path('dob_media'), 0755, true);
                }
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                file_put_contents(public_path('dob_media/' . $fileName), base64_decode($fileData));
                $filePath = 'dob_media/' . $fileName;
            } else {
                continue;
            }

            DobMedia::create([
                'dob_entry_id' => $entry->id,
                'file_url' => $filePath,
            ]);
        }

        // Notifications (same as store)
        try {
            Notify::toDashboard(
                null,
                'alert',
                'DOB Updated',
                'DOB updated by ' . $employee->fore_name . ' ' . $employee->sur_name,
                '/documents/report'
            );
        } catch (\Exception $e) {
            \Log::error('Dashboard notification failed: ' . $e->getMessage());
        }

        try {
            send_push_notification(
                $user->id,
                'DOB Updated',
                'Your DOB has been updated successfully.',
                ['employee' => $employee->id]
            );
        } catch (\Exception $e) {
            \Log::error('Push notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'entry_id' => $entry->id,
            'message' => 'DOB entry updated successfully',
        ], 200);
    }
}
