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

class DobApiController extends Controller
{
    public function store(Request $req)
    {
        $data = $req->validate([
            'shift_id' => 'required|exists:shift_dates,id',
            'entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
            'title' => 'required|string',
            'description' => 'required|string',
            'media_files' => 'nullable|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);

        $user = Auth::user(); // Get the authenticated user
        $employee = Employee::where('user_id', $user->id)->first();
        $entry = DobEntry::create([
            'user_id' => $employee->id,
            'shift_id' => $data['shift_id'],
            'entry_type' => $data['entry_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => json_encode($data['location']),
            'timestamp' => $data['timestamp'],
        ]);

        if (!empty($data['media_files'])) {
            foreach ($data['media_files'] as $file) {
                // ideally store and return real URLs
                DobMedia::create([
                    'dob_entry_id' => $entry->id,
                    'file_url' => $file
                ]);
            }
        }

        $user = Auth::user(); // Get the authenticated user
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'No employee record linked to this user.'], 404);
        }
        Notify::toDashboard(
            null,
            'alert',
            'DOB Uploaded',
            'DOB uploaded by ' . $employee->fore_name . ' ' . $employee->sur_name,
            '/employee'
        );

        Notify::toDashboard(
            $employee->id,
            'alert',
            'DOB Uploaded',
            'You have submitted DOB file',
            '/employee'
        );

            send_push_notification(
                $user->id,
                'You uploaded a DOB',
                'Your DOB has been uploaded succesfully.',
                ['employee' => $employee->id],
            );

        return response()->json([
            'entry_id' => $entry->id,
            'message' => 'DOB entry created',
        ], 201);
    }

    public function obindex(Request $req)
    {
        $q = DobEntry::with('media')
            ->where('user_id', Auth::id());

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

    public function update(Request $req, $id)
    {
        $data = $req->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'additional_media' => 'nullable|array',
        ]);

        $entry = DobEntry::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $entry->update([
            'title' => $data['title'],
            'description' => $data['description'],
        ]);

        if (!empty($data['additional_media'])) {
            foreach ($data['additional_media'] as $file) {
                DobMedia::create([
                    'dob_entry_id' => $entry->id,
                    'file_url' => $file
                ]);
            }
        }

        return response()->json([
            'message' => 'DOB entry updated',
        ]);
    }
}
