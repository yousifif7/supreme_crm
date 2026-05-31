<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\DobEntry;
use App\Models\DobMedia;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Traits\MediaWatermark;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\FileCompressor;
use Illuminate\Support\Facades\Log;
use App\Helpers\Logger;

class DobApiController extends Controller
{
    use MediaWatermark;

    public function store(Request $request)
    {
        // Support bulk submissions: { dobs: [ { shift_id, entry_type, title, description, media_files, location, timestamp }, ... ] }
        $payload = $request->all();

        $user = Auth::user();

        // Bulk path
        if (!empty($payload['dobs']) && is_array($payload['dobs'])) {
            $rules = [
                'dobs' => 'nullable|array|min:1',
                'dobs.*.shift_id' => 'required|exists:shift_dates,id',
                'dobs.*.entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
                'dobs.*.title' => 'required|string',
                'dobs.*.description' => 'required|string',
                'dobs.*.media_files' => 'nullable|array',
                'dobs.*.media_files.*' => 'nullable',
                'dobs.*.location.latitude' => 'required|numeric',
                'dobs.*.location.longitude' => 'required|numeric',
                'dobs.*.timestamp' => 'nullable|date',
            ];

            $request->validate($rules);

            $created = [];
            foreach ($payload['dobs'] as $item) {
                $created[] = $this->createApiDobEntryFromPayload($item, $user);
            }

            return response()->json(['message' => 'DOB entries created', 'created' => $created], 201);
        }

        // Single entry (legacy) behavior — validate and create
        $data = $request->validate([
            'shift_id' => 'nullable|exists:shift_dates,id',
            'entry_type' => 'required|in:incident,observation,maintenance,visitor,other',
            'title' => 'required|string',
            'description' => 'required|string',
            'media_files' => 'nullable|array',
            'media_files.*' => 'nullable', // file upload or base64
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'timestamp' => 'nullable|date',
        ]);

        $created = $this->createApiDobEntryFromPayload($data, $user);

        return response()->json(['entry_id' => $created['id'], 'message' => 'DOB entry created successfully'], 201);
    }

    /**
     * Create an API DOB entry and handle media, notifications, logging.
     * Returns basic created info.
     */
    private function createApiDobEntryFromPayload(array $payload, $user)
    {
        $guardName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Guard');

        // Derive admin_id from the guard's user record so the owning admin can see this entry
        $adminId = \App\Models\User::withoutGlobalScope('admin_scope')
            ->where('id', $user->id)
            ->value('admin_id');

        $entry = DobEntry::create([
            'admin_id' => $adminId,
            'user_id' => $user->id,
            'shift_id' => $payload['shift_id'],
            'entry_type' => $payload['entry_type'],
            'title' => $payload['title'],
            'description' => $payload['description'],
            'location' => json_encode($payload['location']),
            'timestamp' => $payload['timestamp'] ?? Carbon::now(),
        ]);

        // Build the base watermark/timestamp data (employee, coordinates, resolved address)
        $baseTimestampData = $this->buildWatermarkData($user, $payload['location'] ?? []);

        foreach ($payload['media_files'] ?? [] as $file) {
            $filePath = null;

            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('dob_media'), $filename);
                $filePath = 'dob_media/' . $filename;
                try {
                    (new FileCompressor())->compress(public_path('dob_media/' . $filename));
                } catch (\Exception $e) {
                    Log::error('DobApiController: compression failed for uploaded file: ' . $e->getMessage());
                }
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
                try {
                    (new FileCompressor())->compress(public_path('dob_media/' . $filename));
                } catch (\Exception $e) {
                    Log::error('DobApiController: compression failed for base64 file: ' . $e->getMessage());
                }
            } else {
                continue;
            }

            // Burn the timestamp / location watermark onto the saved media
            $this->stampMediaFile(public_path($filePath), $baseTimestampData, $payload['timestamp'] ?? null);

            DobMedia::create([
                'dob_entry_id' => $entry->id,
                'file_url' => $filePath,
            ]);
        }

        try {
            Notify::toDashboard(
                null,
                'alert',
                'DOB uploaded',
                'DOB uploaded by guard ' . $guardName,
                '/dobs'
            );
        } catch (\Throwable $e) {
            Log::error('DobApiController: dashboard notify failed: ' . $e->getMessage());
        }

        try {
            Logger::log($entry, 'Created', 'DOB entry created via API', $user);
        } catch (\Exception $e) {
            Log::error('Logger failed for DOB store: ' . $e->getMessage());
        }

        return ['id' => $entry->id, 'shift_id' => $entry->shift_id];
    }

    public function index(Request $req)
    {
        $q = DobEntry::with('media')
            ->latest('created_at')
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
                // 👇 this will always be an array (empty if no media)
                'media_urls' => $e->media ? $e->media->pluck('file_url')->toArray() : [],
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
        $guardName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Guard');

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

        // Build the base watermark/timestamp data (employee, coordinates, resolved address)
        $baseTimestampData = $this->buildWatermarkData($user, $data['location'] ?? []);

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

            // Burn the timestamp / location watermark onto the saved media
            $this->stampMediaFile(public_path($filePath), $baseTimestampData, $data['timestamp'] ?? null);

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
                'DOB updated by ' . $guardName,
                '/documents/report'
            );
        } catch (\Exception $e) {
            Log::error('Dashboard notification failed: ' . $e->getMessage());
        }

        try {
            Logger::log($entry, 'Updated', 'DOB entry updated via API');
        } catch (\Exception $e) {
            Log::error('Logger failed for DOB update: ' . $e->getMessage());
        }
        // Notification to guard removed - only admin notifications kept

        return response()->json([
            'entry_id' => $entry->id,
            'message' => 'DOB entry updated successfully',
        ], 200);
    }

    /**
     * Get a single DOB entry by ID
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $entry = DobEntry::with('media')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'DOB entry not found'], 404);
        }

        return response()->json([
            'entry' => [
                'id' => $entry->id,
                'shift_id' => $entry->shift_id,
                'entry_type' => $entry->entry_type,
                'title' => $entry->title,
                'description' => $entry->description,
                'media_urls' => $entry->media ? $entry->media->pluck('file_url')->toArray() : [],
                'location' => json_decode($entry->location),
                'timestamp' => $entry->timestamp,
                'admin_comments' => $entry->admin_comments,
                'edit_requested' => $entry->edit_requested,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
            ]
        ]);
    }
}
