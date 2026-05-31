<?php

namespace App\Http\Controllers\API;

use Notify;
use Carbon\Carbon;
use App\Models\DobEntry;
use App\Models\DobMedia;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Services\GeoService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\FileCompressor;
use Illuminate\Support\Facades\Log;
use App\Helpers\Logger;

class DobApiController extends Controller
{
    public function store(Request $request)
    {
        // Support bulk submissions: { dobs: [ { shift_id, entry_type, title, description, media_files, location, timestamp }, ... ] }
        $payload = $request->all();

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'No employee linked to this user.'], 404);
        }

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
                $created[] = $this->createApiDobEntryFromPayload($item, $user, $employee);
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

        $created = $this->createApiDobEntryFromPayload($data, $user, $employee);

        return response()->json(['entry_id' => $created['id'], 'message' => 'DOB entry created successfully'], 201);
    }

    /**
     * Create an API DOB entry and handle media, notifications, logging.
     * Returns basic created info.
     */
    private function createApiDobEntryFromPayload(array $payload, $user, $employee)
    {
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
        $baseTimestampData = $this->buildTimestampData($user, $payload['location'] ?? []);

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
            $this->stampMedia(public_path($filePath), $baseTimestampData, $payload['timestamp'] ?? null);

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
                'DOB uploaded by guard ' . $employee->fore_name . ' ' . $employee->sur_name,
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
        $employee = Employee::where('user_id', Auth::id())->first();
        $q = DobEntry::with('media')
            ->latest('created_at')
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

        // Build the base watermark/timestamp data (employee, coordinates, resolved address)
        $baseTimestampData = $this->buildTimestampData($user, $data['location'] ?? []);

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
            $this->stampMedia(public_path($filePath), $baseTimestampData, $data['timestamp'] ?? null);

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

    /*
    |--------------------------------------------------------------------------
    | Timestamp / watermark helpers (mirrors CheckCallController behaviour)
    |--------------------------------------------------------------------------
    */

    /**
     * Build the base watermark data (employee, coordinates, resolved address)
     * from the authenticated user and the submitted location payload.
     */
    private function buildTimestampData($user, $location): array
    {
        $lat = $location['latitude'] ?? null;
        $lng = $location['longitude'] ?? null;

        $resolvedAddress = null;
        if ($lat !== null && $lng !== null) {
            try {
                $resolvedAddress = (new GeoService())->getAddressFromCoordinates($lat, $lng);
            } catch (\Exception $e) {
                Log::warning('DobApiController: GeoService failed: ' . $e->getMessage());
            }
        }

        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($userName === '') {
            $userName = trim(($user->name ?? '') ?: 'N/A');
        }

        return [
            'employee'  => $userName,
            'latitude'  => $lat ?? 'N/A',
            'longitude' => $lng ?? 'N/A',
            'location'  => $resolvedAddress ?? 'N/A',
        ];
    }

    /**
     * Stamp a saved media file with the timestamp/location watermark.
     * Images get a burned-in watermark, videos get an overlay, everything
     * else gets a sidecar metadata file.
     */
    private function stampMedia($fullPath, array $baseTimestampData, $captureTimestamp = null): void
    {
        if (!file_exists($fullPath)) {
            return;
        }

        $timestampData = $baseTimestampData;

        if ($captureTimestamp) {
            $captureTime = $this->parseUKTimestamp($captureTimestamp);
            $timestampData['time'] = $captureTime->format('d/m/Y H:i:s');
        } else {
            $timestampData['time'] = Carbon::now('Europe/London')->format('d/m/Y H:i:s');
        }

        $fileType = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        try {
            switch ($fileType) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $this->addWatermarkToImage($fullPath, $timestampData);
                    break;

                case 'mp4':
                case 'mov':
                case 'avi':
                case 'mkv':
                    $this->addTimestampToVideo($fullPath, $timestampData);
                    break;

                default:
                    $this->createMetadataFile($fullPath, $timestampData);
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('DobApiController: failed to stamp media ' . $fullPath . ': ' . $e->getMessage());
        }
    }

    /**
     * Parse a UK-based timestamp string and return a Carbon instance.
     */
    private function parseUKTimestamp($timestamp)
    {
        try {
            if (preg_match('/[Z\+\-]\d{2}:?\d{2}$/', $timestamp) || str_ends_with($timestamp, 'Z')) {
                return Carbon::parse($timestamp)->setTimezone('Europe/London');
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $timestamp)) {
                return Carbon::parse($timestamp);
            }

            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $timestamp)) {
                return Carbon::createFromFormat('d/m/Y H:i:s', $timestamp)
                    ?? Carbon::createFromFormat('d/m/Y H:i', $timestamp)
                    ?? Carbon::createFromFormat('d/m/Y', $timestamp)
                    ?? Carbon::parse($timestamp);
            }

            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            Log::warning('DobApiController: failed to parse timestamp: ' . $timestamp . ' - ' . $e->getMessage());
            return Carbon::now('Europe/London');
        }
    }

    /**
     * Burn a timestamp/location watermark into an image (top-left block).
     */
    private function addWatermarkToImage($imagePath, $timestampData)
    {
        $img = null;
        $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($imagePath);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($imagePath);
        }

        if (!$img) return;

        $white = imagecolorallocate($img, 255, 255, 255);
        $blackTrans = imagecolorallocatealpha($img, 0, 0, 0, 80);

        $locationText = 'Unknown';
        if (is_array($timestampData['location'] ?? null)) {
            $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
        } else {
            $locationText = $timestampData['location'] ?? 'Unknown';
        }

        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  " .
            "Lng: " . ($timestampData['longitude'] ?? '') .
            "\nLocation: " . $locationText;

        $lines = explode("\n", $text);
        $fontPath = public_path('fonts/Arial.ttf');

        if (!file_exists($fontPath)) {
            $this->addWatermarkWithGDFont($img, $text, $imagePath, $ext);
            return;
        }

        $imgWidth = imagesx($img);
        $imgHeight = imagesy($img);

        $padding = max(12, intval($imgWidth * 0.02));
        $maxRectWidth = max(100, intval($imgWidth * 0.9) - 2 * $padding);

        $fontSize = max(14, intval($imgWidth * 0.03));
        $minFontSize = 10;

        $splitLongWord = function ($word, $fontSizeLocal) use ($fontPath, $maxRectWidth) {
            $pieces = [];
            $len = mb_strlen($word);
            $start = 0;
            while ($start < $len) {
                $part = '';
                for ($i = $start; $i < $len; $i++) {
                    $test = $part . mb_substr($word, $i, 1);
                    $bb = imagettfbbox($fontSizeLocal, 0, $fontPath, $test);
                    $w = abs($bb[4] - $bb[0]);
                    if ($w > $maxRectWidth) break;
                    $part = $test;
                }
                if ($part === '') {
                    $part = mb_substr($word, $start, 1);
                    $start++;
                } else {
                    $start += mb_strlen($part);
                }
                $pieces[] = $part;
            }
            return $pieces;
        };

        while (true) {
            $lineHeight = max(12, intval($fontSize * 1.18));
            $wrapped = [];

            foreach ($lines as $line) {
                $words = preg_split('/\s+/', trim($line));
                $current = '';
                foreach ($words as $w) {
                    $test = $current === '' ? $w : $current . ' ' . $w;
                    $bb = imagettfbbox($fontSize, 0, $fontPath, $test);
                    $wWidth = abs($bb[4] - $bb[0]);
                    if ($wWidth > $maxRectWidth) {
                        if ($current === '') {
                            $pieces = $splitLongWord($w, $fontSize);
                            foreach ($pieces as $p) $wrapped[] = $p;
                            $current = '';
                        } else {
                            $wrapped[] = $current;
                            $current = $w;
                        }
                    } else {
                        $current = $test;
                    }
                }
                if (strlen($current)) $wrapped[] = $current;
            }

            $rectWidth = 0;
            foreach ($wrapped as $rl) {
                $bb = imagettfbbox($fontSize, 0, $fontPath, $rl);
                $w = abs($bb[4] - $bb[0]);
                if ($w > $rectWidth) $rectWidth = $w;
            }
            $rectWidth = min($rectWidth, $maxRectWidth);
            $rectHeight = count($wrapped) * $lineHeight + 2 * $padding;

            if ($rectHeight > intval($imgHeight * 0.5) && $fontSize > $minFontSize) {
                $fontSize = max($minFontSize, $fontSize - 2);
                continue;
            }
            break;
        }

        imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

        $x = $padding;
        $y = $padding + $fontSize;
        foreach ($wrapped as $rl) {
            imagettftext($img, $fontSize, 0, $x, $y, $white, $fontPath, $rl);
            $y += $lineHeight;
        }

        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($img, $imagePath, 90);
        } else {
            imagepng($img, $imagePath);
        }

        imagedestroy($img);
    }

    /**
     * Fallback watermark renderer using any available system TTF, or GD fonts.
     */
    private function addWatermarkWithGDFont($img, $text, $imagePath, $ext)
    {
        $lines = explode("\n", $text);

        $fontCandidates = [
            public_path('fonts/Arial.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/Library/Fonts/Arial.ttf',
            'C:\Windows\Fonts\arial.ttf',
        ];

        $availableFont = null;
        foreach ($fontCandidates as $path) {
            if ($path && file_exists($path)) {
                $availableFont = $path;
                break;
            }
        }

        $imgWidth = imagesx($img);
        $imgHeight = imagesy($img);

        $blackTrans = imagecolorallocatealpha($img, 0, 0, 0, 80);
        $white = imagecolorallocate($img, 255, 255, 255);

        $targetPercent = 0.15;
        if ($availableFont) {
            $fontSize = max(40, intval($imgHeight * $targetPercent));
            $lineHeight = intval($fontSize * 1.05);
            $padding = intval($fontSize * 0.35);

            $maxRectWidth = intval($imgWidth * 0.9);
            $wrapped = [];
            foreach ($lines as $line) {
                $words = preg_split('/\s+/', trim($line));
                $current = '';
                foreach ($words as $w) {
                    $test = trim(($current === '' ? '' : $current . ' ') . $w);
                    $bbox = imagettfbbox($fontSize, 0, $availableFont, $test);
                    $wWidth = abs($bbox[4] - $bbox[0]);
                    if ($wWidth > ($maxRectWidth - 2 * $padding)) {
                        if ($current === '') {
                            $wrapped[] = $test;
                            $current = '';
                        } else {
                            $wrapped[] = $current;
                            $current = $w;
                        }
                    } else {
                        $current = $test;
                    }
                }
                if (strlen($current)) $wrapped[] = $current;
            }

            $rectWidth = 0;
            foreach ($wrapped as $rl) {
                $bb = imagettfbbox($fontSize, 0, $availableFont, $rl);
                $w = abs($bb[4] - $bb[0]);
                if ($w > $rectWidth) $rectWidth = $w;
            }
            $rectWidth = min(max($rectWidth, intval($imgWidth * 0.4)), $maxRectWidth - 2 * $padding);

            $rectHeight = count($wrapped) * $lineHeight + 2 * $padding;

            imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

            $x = $padding;
            $y = $padding + $fontSize;
            foreach ($wrapped as $rl) {
                imagettftext($img, $fontSize, 0, $x, $y, $white, $availableFont, $rl);
                $y += $lineHeight;
            }
        } else {
            $font = 5;
            $fontHeight = imagefontheight($font);

            $padding = 22;
            $rectWidth = min(intval($imgWidth * 0.9) - 2 * $padding, 1200);
            $rectHeight = count($lines) * ($fontHeight + 8) + 2 * $padding;

            imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

            $thinWhite = imagecolorallocatealpha($img, 255, 255, 255, 10);
            $y = $padding;
            foreach ($lines as $line) {
                imagestring($img, $font, $padding, $y, $line, $thinWhite);
                $y += $fontHeight + 8;
            }
        }

        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($img, $imagePath, 90);
        } else {
            imagepng($img, $imagePath);
        }

        imagedestroy($img);
    }

    /**
     * Overlay a timestamp/location watermark onto a video using ffmpeg.
     * Silently skips if ffmpeg is unavailable.
     */
    private function addTimestampToVideo($videoPath, $timestampData)
    {
        $ffmpegBin = null;
        $candidates = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/bin/ffmpeg',
            'C:/ffmpeg/bin/ffmpeg.exe',
            'C:/Program Files/ffmpeg/bin/ffmpeg.exe',
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        if (!$ffmpegBin && function_exists('shell_exec')) {
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) {
                $ffmpegBin = $found;
            } else {
                $found = trim((string) @shell_exec('where ffmpeg 2>NUL'));
                if ($found) {
                    $lines = explode("\n", $found);
                    $ffmpegBin = trim($lines[0]);
                }
            }
        }
        if (!$ffmpegBin) {
            Log::info('DobApiController: ffmpeg not found, skipping video watermark', ['path' => $videoPath]);
            return;
        }

        $outputPath = $videoPath . '.wm_' . uniqid() . '.mp4';

        $locationText = 'Unknown';
        if (is_array($timestampData['location'] ?? null)) {
            $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
        } else {
            $locationText = $timestampData['location'] ?? 'Unknown';
        }
        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  Lng: " . ($timestampData['longitude'] ?? '') .
            "\nLocation: " . $locationText;

        $fontPath = public_path('fonts/Arial.ttf');
        $fontSize = 14;
        $im = imagecreatetruecolor(500, 120);
        imagesavealpha($im, true);
        $bgColor = imagecolorallocatealpha($im, 0, 0, 0, 60);
        imagefill($im, 0, 0, $bgColor);
        $white = imagecolorallocate($im, 255, 255, 255);
        if (file_exists($fontPath)) {
            $y = 18;
            foreach (explode("\n", $text) as $line) {
                imagettftext($im, $fontSize, 0, 8, $y, $white, $fontPath, $line);
                $y += $fontSize + 4;
            }
        } else {
            $y = 5;
            foreach (explode("\n", $text) as $line) {
                imagestring($im, 3, 5, $y, $line, $white);
                $y += 14;
            }
        }
        $textImage = sys_get_temp_dir() . '/dob_overlay_' . uniqid() . '.png';
        imagepng($im, $textImage);
        imagedestroy($im);

        $cmd = escapeshellcmd($ffmpegBin)
            . ' -i ' . escapeshellarg($videoPath)
            . ' -i ' . escapeshellarg($textImage)
            . ' -filter_complex "overlay=10:10"'
            . ' -c:a copy '
            . escapeshellarg($outputPath) . ' -y';

        $out = [];
        $ret = 0;
        exec($cmd . ' 2>&1', $out, $ret);
        @unlink($textImage);

        if ($ret === 0 && file_exists($outputPath)) {
            @unlink($videoPath);
            if (!@rename($outputPath, $videoPath)) {
                @copy($outputPath, $videoPath);
                @unlink($outputPath);
            }
            @chmod($videoPath, 0644);
        } else {
            Log::warning('DobApiController: ffmpeg video watermark failed', [
                'path'   => $videoPath,
                'return' => $ret,
                'output' => implode("\n", $out),
            ]);
            if (file_exists($outputPath)) @unlink($outputPath);
        }
    }

    /**
     * Write a sidecar metadata file for non-image/video media (PDF, doc, etc.).
     */
    private function createMetadataFile($filePath, $timestampData)
    {
        $locationText = is_array($timestampData['location'] ?? null)
            ? json_encode($timestampData['location'])
            : ($timestampData['location'] ?? 'Unknown');

        $metadataPath = $filePath . '.metadata.txt';
        $content = "DOB MEDIA METADATA\n";
        $content .= "==================\n";
        $content .= "Time: " . ($timestampData['time'] ?? '') . "\n";
        $content .= "Employee: " . ($timestampData['employee'] ?? '') . "\n";
        $content .= "Latitude: " . ($timestampData['latitude'] ?? '') . "\n";
        $content .= "Longitude: " . ($timestampData['longitude'] ?? '') . "\n";
        $content .= "Location: " . $locationText . "\n";
        $content .= "Original File: " . basename($filePath) . "\n";

        @file_put_contents($metadataPath, $content);
    }
}
