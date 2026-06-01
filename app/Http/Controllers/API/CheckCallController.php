<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Helpers\Logger;
use App\Models\Employee;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use App\Models\Notification;
use App\Services\GeoService;
use Illuminate\Http\Request;
use App\Models\CheckCallMedia;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
        'media' => 'nullable|array',
        'media.*.timestamp' => 'nullable|string',
        'location.latitude' => 'required|numeric',
        'location.longitude' => 'required|numeric',
        'notes' => 'nullable|string',
        'timestamp' => 'nullable|date',
    ]);

    Log::info('Incoming request', [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'headers' => $request->headers->all(),
        'body' => $request->all(),
        'ip' => $request->ip(),
    ]);

    $checkCall = CheckCall::findOrFail($id);

    $user = Auth::user();

    if ($checkCall->status == 'completed') {
        return response()->json([
            'message' => 'This CheckCall has already been completed.'
        ], 404);
    }

    if ($checkCall->status == 'missed') {
        return response()->json([
            'message' => 'This CheckCall has already been missed, You cannot submit unless an Admin gave permission to.'
        ], 422);
    }

    // IMPORTANT: get raw media from request to preserve UploadedFile objects
$mediaItems = [];

// New structured format
if ($request->has('media')) {

    $mediaItems = $request->all()['media'] ?? [];
}

// Old multipart upload format
elseif ($request->hasFile('media_files')) {

    foreach ($request->file('media_files') as $file) {

        $mediaItems[] = [
            'media_file' => $file,
            'timestamp' => now()->toISOString(),
        ];
    }
}
    if (
        $checkCall->require_media == '1'
        && count($mediaItems) == 0
    ) {
        return response()->json([
            'message' => 'This check call requires media evidence. Please attach media files before completing.'
        ], 422);
    }

    $now = Carbon::now();

    $scheduledUtc = Carbon::parse(
        $checkCall->scheduled_time,
        'UTC'
    );

    $earliest = $scheduledUtc->copy()->subMinutes(5);

    $latest = $scheduledUtc->copy()->addMinutes(15);

    // Prepare base timestamp data
    $shiftdate = ShiftDate::find($checkCall->shift_id);

    if (!$shiftdate) {
        return response()->json([
            'message' => 'Shift date not found for this check call.'
        ], 404);
    }

    if ($shiftdate->staff_id !== $user->id) {
        return response()->json([
            'message' => 'You are not assigned to this shift and cannot complete this check call.'
        ], 403);
    }

    if (
        $shiftdate->is_assign !== 3
        && $shiftdate->status !== 'booked_on'
    ) {
        return response()->json([
            'message' => 'You must book on for this shift before completing the check call.'
        ], 422);
    }

    $lat = $data['location']['latitude'];

    $lng = $data['location']['longitude'];

    // Resolve address
    $geoService = new GeoService();

    $resolvedAddress = null;

    try {

        $resolvedAddress = $geoService
            ->getAddressFromCoordinates($lat, $lng);

    } catch (\Exception $e) {

        Log::warning(
            'GeoService failed: ' . $e->getMessage()
        );
    }

    // Base timestamp data
    $userName = trim(
        ($user->first_name ?? '')
        . ' '
        . ($user->last_name ?? '')
    );

    $baseTimestampData = [
        'employee' => $userName,
        'latitude' => $lat,
        'longitude' => $lng,
        'site' => $shiftdate->shift->site->site_name ?? 'N/A',
        'location' => $resolvedAddress
            ?? ($shiftdate->shift->site->address ?? 'N/A')
    ];

    @set_time_limit(0);

    $processedFiles = [];

    $mediaIndex = 0;

    foreach ($mediaItems as $mediaItem) {

        $mediaIndex++;

        $file = $mediaItem['media_file'] ?? null;

        $captureTimestamp =
            $mediaItem['timestamp'] ?? null;

        if (!$file) {
            continue;
        }

        $filePath = null;

        $originalName = null;

        /*
        |--------------------------------------------------------------------------
        | Uploaded File
        |--------------------------------------------------------------------------
        */

        if ($file instanceof \Illuminate\Http\UploadedFile) {

            $originalName =
                $file->getClientOriginalName();

            $extension =
                $file->getClientOriginalExtension();

            $filename =
                time() . '_' . uniqid() . '.' . $extension;

            if (!file_exists(public_path('check_calls'))) {

                mkdir(
                    public_path('check_calls'),
                    0755,
                    true
                );
            }

            $file->move(
                public_path('check_calls'),
                $filename
            );

            $filePath = 'check_calls/' . $filename;
        }

        /*
        |--------------------------------------------------------------------------
        | Base64
        |--------------------------------------------------------------------------
        */

        elseif (
            is_string($file)
            && preg_match('/^data:/', $file)
        ) {

            $fileData = preg_replace(
                '/^data:\w+\/\w+;base64,/',
                '',
                $file
            );

            $extension = 'png';

            if (
                preg_match(
                    '/^data:(\w+\/\w+);base64,/',
                    $file,
                    $matches
                )
            ) {

                $mime = $matches[1];

                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'video/mp4' => 'mp4',
                    'video/quicktime' => 'mov',
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                ];

                $extension = $extMap[$mime] ?? 'bin';
            }

            if (!file_exists(public_path('check_calls'))) {

                mkdir(
                    public_path('check_calls'),
                    0755,
                    true
                );
            }

            $filename =
                time() . '_' . uniqid() . '.' . $extension;

            file_put_contents(
                public_path('check_calls/' . $filename),
                base64_decode($fileData)
            );

            $filePath = 'check_calls/' . $filename;
        }

        else {

            continue;
        }

        $fullPath = public_path($filePath);

        $fileType = strtolower(
            pathinfo($fullPath, PATHINFO_EXTENSION)
        );

        /*
        |--------------------------------------------------------------------------
        | Timestamp Data
        |--------------------------------------------------------------------------
        */

        $timestampData = $baseTimestampData;

        if ($captureTimestamp) {

            $captureTime =
                $this->parseUKTimestamp($captureTimestamp);

            $timestampData['time'] =
                $captureTime->format('d/m/Y H:i:s');

            $timestampData['capture_time_unix'] =
                $captureTime->timestamp;

        } else {

            $timestampData['time'] =
                Carbon::now('Europe/London')
                    ->format('d/m/Y H:i:s');
        }

        /*
        |--------------------------------------------------------------------------
        | Process Media
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $fileType,
                ['mp4', 'mov', 'avi', 'mkv']
            )
        ) {

            $this->processVideo(
                $fullPath,
                $timestampData
            );

        } else {

            $compressedPath = $this->compressFile(
                $fullPath,
                $fileType
            );

            if (
                $compressedPath
                && $compressedPath != $fullPath
            ) {

                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                rename($compressedPath, $fullPath);

                @chmod($fullPath, 0644);
            }

            switch ($fileType) {

                case 'jpg':
                case 'jpeg':
                case 'png':

                    $this->addWatermarkToImage(
                        $fullPath,
                        $timestampData
                    );

                    break;

                case 'pdf':

                    $this->addTimestampToPdf(
                        $fullPath,
                        $timestampData
                    );

                    break;

                case 'doc':
                case 'docx':

                    $this->addTimestampToDocument(
                        $fullPath,
                        $timestampData
                    );

                    break;

                default:

                    $this->createMetadataFile(
                        $fullPath,
                        $timestampData
                    );

                    break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save Media
        |--------------------------------------------------------------------------
        */

        CheckCallMedia::create([
            'check_call_id' => $checkCall->id,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'original_name' =>
                $originalName ?? "media_{$mediaIndex}",
            'file_size' => filesize($fullPath),
            'captured_at' =>
                isset($timestampData['capture_time_unix'])
                    ? Carbon::createFromTimestamp(
                        $timestampData['capture_time_unix']
                    )
                    : Carbon::now(),
        ]);

        // Collect processed files
        if (file_exists($fullPath)) {

            $processedFiles[] = $fullPath;
        }

        $metaPath = $fullPath . '.metadata.txt';

        if (file_exists($metaPath)) {

            $processedFiles[] = $metaPath;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Check Call
    |--------------------------------------------------------------------------
    */

    $checkCall->status = 'completed';

    $checkCall->approval_status = 'pending';

    $checkCall->employee_id = $user->id;

    $checkCall->notes = $data['notes'] ?? null;

    $checkCall->completed_at = Carbon::now();

    $checkCall->save();

    /*
    |--------------------------------------------------------------------------
    | Store Location
    |--------------------------------------------------------------------------
    */

    Location::create([
        'user_id' => $user->id,
        'latitude' => $lat,
        'longitude' => $lng,
        'accuracy' => 100,
        'on_duty' => 1,
        'shiftdate_id' => $checkCall->shift_id,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    try {

        Notification::create([
            'user_id' => 1,
            'employee_id' => null,
            'type' => 'alert',
            'title' => 'Checkcall completed',
            'message' =>
                'Guard '
                . $userName
                . ' completed checkcall '
                . $checkCall->name,
            'read' => false,
            'action_url' =>
                "/shift-dates/{$checkCall->shift_id}/view"
        ]);

    } catch (\Exception $e) {

        Log::error(
            'Notification failed: '
            . $e->getMessage()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Response Media URLs
    |--------------------------------------------------------------------------
    */

    $mediaUrls = [];

    foreach ($processedFiles as $p) {

        $relative = ltrim(
            str_replace(public_path(), '', $p),
            '\\/'
        );

        $mediaUrls[] = [
            'file_path' => $relative,
            'url' => asset($relative),
        ];
    }

    return response()->json([
        'message' => 'Check call completed successfully',
        'check_call_id' => $checkCall->id,
        'media' => $mediaUrls,
        'media_count' => count($mediaItems),
    ], 200);
}
/**
 * Parse a UK-based timestamp string and return a Carbon instance
 * Supports multiple formats (timestamp already in UK time):
 * - "2024-01-15T14:30:45Z" (ISO 8601 UTC)
 * - "2024-01-15T14:30:45+00:00" (ISO 8601 with offset)
 * - "2024-01-15 14:30:45" (ISO with time)
 * - "15/01/2024 14:30:45" (UK format with time)
 * - "15/01/2024 2:30 PM" (UK format with 12h time)
 * 
 * @param string $timestamp
 * @return \Carbon\Carbon
 */
private function parseUKTimestamp($timestamp)
{
    try {
        // If the timestamp has timezone info (Z, +00:00, etc), parse it as-is
        // Carbon will handle the conversion automatically
        if (preg_match('/[Z\+\-]\d{2}:?\d{2}$/', $timestamp) || str_ends_with($timestamp, 'Z')) {
            // Parse as timezone-aware string (will be in UTC/offset)
            $carbon = Carbon::parse($timestamp);
            // Convert to London time for storage/display
            return $carbon->setTimezone('Europe/London');
        }

        // Try ISO format first (no timezone info)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $timestamp)) {
            return Carbon::parse($timestamp);
        }

        // Try DD/MM/YYYY format (UK standard, assume it's already in UK time)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $timestamp)) {
            return Carbon::createFromFormat('d/m/Y H:i:s', $timestamp)
                ?? Carbon::createFromFormat('d/m/Y H:i', $timestamp)
                ?? Carbon::createFromFormat('d/m/Y', $timestamp)
                ?? Carbon::parse($timestamp);
        }

        // Fallback to generic parsing (assumes server timezone)
        return Carbon::parse($timestamp);
    } catch (\Exception $e) {
        Log::warning('Failed to parse timestamp: ' . $timestamp . ' - ' . $e->getMessage());
        return Carbon::now('Europe/London');
    }
}
	
	// Compression methods for different file types
    private function compressFile($filePath, $fileType)
    {
        $originalSize = filesize($filePath);
        $maxSize = 5 * 1024 * 1024; // 5MB limit

        if ($originalSize <= $maxSize) {
            return $filePath; // No compression needed
        }

        switch ($fileType) {
            case 'jpg':
            case 'jpeg':
                return $this->compressImage($filePath, 60, 1920); // 60% quality, max width 1920px
            case 'png':
                return $this->compressImage($filePath, 8, 1920); // PNG compression level 8, max width 1920px
            case 'mp4':
            case 'mov':
            case 'avi':
                return $this->compressVideo($filePath);
            case 'pdf':
                return $this->compressPdf($filePath);
            default:
                return $filePath; // No compression for other types
        }
    }

    private function compressImage($filePath, $quality, $maxWidth = 1920)
    {
        $img = null;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = imagecreatefromjpeg($filePath);
        } elseif ($ext === 'png') {
            $img = imagecreatefrompng($filePath);
        } else {
            return $filePath;
        }

        if (!$img) return $filePath;

        // Get original dimensions
        $originalWidth = imagesx($img);
        $originalHeight = imagesy($img);

        // Calculate new dimensions if needed
        if ($originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = intval($originalHeight * $maxWidth / $originalWidth);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Create new image with new dimensions
        $newImg = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($ext === 'png') {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Create compressed file path
        $compressedPath = $filePath . '.compressed.' . $ext;

        // Save compressed image
        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($newImg, $compressedPath, $quality);
        } elseif ($ext === 'png') {
            imagepng($newImg, $compressedPath, $quality); // PNG quality is 0-9
        }

        // Free memory
        imagedestroy($img);
        imagedestroy($newImg);

        // Check if compression was successful and reduced size
        if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
            return $compressedPath;
        } else {
            // If compression failed or didn't reduce size, use original
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $filePath;
        }
    }

    private function compressVideo($filePath)
    {
        // Detect system ffmpeg dynamically
        $ffmpegBin = null;
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/bin/ffmpeg'] as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        if (!$ffmpegBin && function_exists('shell_exec')) {
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) $ffmpegBin = $found;
        }
        if (!$ffmpegBin) {
            Log::info('compressVideo: ffmpeg not found, skipping compression', ['path' => $filePath]);
            return $filePath;
        }

        $originalSize = filesize($filePath);
        $targetBitrate = '1000k';
        if ($originalSize > 50 * 1024 * 1024) {
            $targetBitrate = '500k';
        } elseif ($originalSize > 20 * 1024 * 1024) {
            $targetBitrate = '800k';
        }

        $compressedPath = $filePath . '.compressed.mp4';
        $cmd = escapeshellcmd($ffmpegBin)
            . ' -i ' . escapeshellarg($filePath)
            . ' -c:v libx264 -crf 28 -preset medium -b:v ' . $targetBitrate
            . ' -c:a aac -b:a 64k -movflags +faststart '
            . escapeshellarg($compressedPath) . ' -y';

        $out = [];
        $ret = 0;
        exec($cmd . ' 2>/dev/null', $out, $ret);

        if ($ret === 0 && file_exists($compressedPath) && filesize($compressedPath) < $originalSize) {
            return $compressedPath;
        }
        if (file_exists($compressedPath)) @unlink($compressedPath);
        return $filePath;
    }

    private function compressPdf($filePath)
    {
        // Check if Ghostscript is available for PDF compression
        if (!shell_exec('which gs')) {
            return $filePath;
        }

        $compressedPath = $filePath . '.compressed.pdf';
        $escapedInput = escapeshellarg($filePath);
        $escapedOutput = escapeshellarg($compressedPath);

        // Ghostscript command for PDF compression
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 " .
            "-dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH " .
            "-sOutputFile={$escapedOutput} {$escapedInput} 2>/dev/null";

        shell_exec($command);

        if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $filePath;
        }
    }


    // Existing timestamp methods (keep these from previous implementation)
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

    // Format location text
    $locationText = 'Unknown';
    if (is_array($timestampData['location'] ?? null)) {
        $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
    } else {
        $locationText = $timestampData['location'] ?? 'Unknown';
    }

    // Build watermark text with UK timestamp
    // The 'time' field should already be in UK format (d/m/Y H:i:s) from parseUKTimestamp()
    $text = "Time: " . ($timestampData['time'] ?? '') .
        "\nEmployee: " . ($timestampData['employee'] ?? '') .
        "\nLat: " . ($timestampData['latitude'] ?? '') . "  " .
        "Lng: " . ($timestampData['longitude'] ?? '') .
        "\nSite: " . ($timestampData['site'] ?? '') .
        "\nLocation: " . $locationText;

    $lines = explode("\n", $text);
    $fontPath = public_path('fonts/Arial.ttf');

    if (!file_exists($fontPath)) {
        // Fallback to GD font if TTF not available
        $this->addWatermarkWithGDFont($img, $text, $imagePath, $ext);
        return;
    }

    $imgWidth = imagesx($img);
    $imgHeight = imagesy($img);

    $padding = max(12, intval($imgWidth * 0.02));
    $maxRectWidth = max(100, intval($imgWidth * 0.9) - 2 * $padding);

    // Start font size relative to image width
    $fontSize = max(14, intval($imgWidth * 0.03));
    $minFontSize = 10;

    // Helper: split a very long 'word' into chunks that fit
    $splitLongWord = function ($word, $fontSizeLocal) use ($fontPath, $maxRectWidth) {
        $pieces = [];
        $len = mb_strlen($word);
        $start = 0;
        while ($start < $len) {
            $part = '';
            // Build char-by-char until it no longer fits
            for ($i = $start; $i < $len; $i++) {
                $test = $part . mb_substr($word, $i, 1);
                $bb = imagettfbbox($fontSizeLocal, 0, $fontPath, $test);
                $w = abs($bb[4] - $bb[0]);
                if ($w > $maxRectWidth) break;
                $part = $test;
            }
            if ($part === '') {
                // single character too wide? force at least one char
                $part = mb_substr($word, $start, 1);
                $start++;
            } else {
                $start += mb_strlen($part);
            }
            $pieces[] = $part;
        }
        return $pieces;
    };

    // Wrap lines and reduce font size if the block is too tall
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
                        // single very long word -> split it
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

        // If the watermark block uses too much vertical space, reduce font
        if ($rectHeight > intval($imgHeight * 0.5) && $fontSize > $minFontSize) {
            $fontSize = max($minFontSize, $fontSize - 2);
            continue; // recalc wrapping with smaller font
        }
        break;
    }

    // Draw background rectangle and text
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

    private function addTimestampToVideo($videoPath, $timestampData)
    {
        // Detect system ffmpeg dynamically
        $ffmpegBin = null;
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/bin/ffmpeg'] as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        if (!$ffmpegBin && function_exists('shell_exec')) {
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) $ffmpegBin = $found;
        }
        if (!$ffmpegBin) {
            Log::info('addTimestampToVideo: ffmpeg not found, skipping video watermark', ['path' => $videoPath]);
            return;
        }

        $outputPath = $videoPath . '.wm_' . uniqid() . '.mp4';
       
        
        // Build overlay text
        $locationText = 'Unknown';
        if (is_array($timestampData['location'] ?? null)) {
            $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
        } else {
            $locationText = $timestampData['location'] ?? 'Unknown';
        }
        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  Lng: " . ($timestampData['longitude'] ?? '') .
            "\nSite: " . ($timestampData['site'] ?? '') .
            "\nLocation: " . $locationText;

        // Generate overlay PNG using GD
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
        // Unique temp filename to avoid race conditions
        $textImage = sys_get_temp_dir() . '/checkcall_overlay_' . uniqid() . '.png';
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
            Log::warning('addTimestampToVideo: ffmpeg watermark failed', [
                'path'   => $videoPath,
                'return' => $ret,
                'output' => implode("\n", $out),
            ]);
            if (file_exists($outputPath)) @unlink($outputPath);
        }
    }



    private function processVideo(string $filePath, array $timestampData): void
    {
        // Enhanced ffmpeg detection including Windows paths
        $ffmpegBin = null;
        $candidates = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/bin/ffmpeg',
            'C:/ffmpeg/bin/ffmpeg.exe',
            'C:/Program Files/ffmpeg/bin/ffmpeg.exe',
            'ffmpeg' // Will use PATH
        ];
        
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $ffmpegBin = $candidate;
                break;
            }
        }
        
        if (!$ffmpegBin && function_exists('shell_exec')) {
            // Try Unix which command
            $found = trim((string) @shell_exec('which ffmpeg 2>/dev/null'));
            if ($found && is_executable($found)) {
                $ffmpegBin = $found;
            } else {
                // Try Windows where command
                $found = trim((string) @shell_exec('where ffmpeg 2>NUL'));
                if ($found) {
                    $lines = explode("\n", $found);
                    $ffmpegBin = trim($lines[0]);
                }
            }
        }
        
        if (!$ffmpegBin) {
            // Try just 'ffmpeg' - might be in PATH
            $ffmpegBin = 'ffmpeg';
        }

        $originalSize = @filesize($filePath) ?: 0;
        
        // AGGRESSIVE compression settings - compress regardless of size
        // Higher CRF = more compression (range: 0-51, 23 is default, we use 30-32 for aggressive compression)
        $crf = 30;
        $targetBitrate = '800k';
        
        if ($originalSize > 100 * 1024 * 1024) {
            // Very large files (>100MB): maximum compression
            $crf = 32;
            $targetBitrate = '400k';
        } elseif ($originalSize > 50 * 1024 * 1024) {
            // Large files (>50MB): high compression
            $crf = 31;
            $targetBitrate = '500k';
        } elseif ($originalSize > 20 * 1024 * 1024) {
            // Medium files (>20MB): moderate-high compression
            $crf = 30;
            $targetBitrate = '600k';
        }
        // Small files still get compressed with CRF 30 and 800k bitrate

        $locationText = 'Unknown';
        if (is_array($timestampData['location'] ?? null)) {
            $locationText = $timestampData['location']['formatted_address'] ?? json_encode($timestampData['location']);
        } else {
            $locationText = (string) ($timestampData['location'] ?? 'Unknown');
        }
        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  Lng: " . ($timestampData['longitude'] ?? '') .
            "\nSite: " . ($timestampData['site'] ?? '') .
            "\nLocation: " . $locationText;

        $fontPath = public_path('fonts/Arial.ttf');
        $im = imagecreatetruecolor(500, 120);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 60));
        $white = imagecolorallocate($im, 255, 255, 255);
        if (file_exists($fontPath)) {
            $y = 18;
            foreach (explode("\n", $text) as $line) { imagettftext($im, 14, 0, 8, $y, $white, $fontPath, $line); $y += 18; }
        } else {
            $y = 5;
            foreach (explode("\n", $text) as $line) { imagestring($im, 3, 5, $y, $line, $white); $y += 14; }
        }
        $textImage = sys_get_temp_dir() . '/checkcall_proc_' . uniqid() . '.png';
        imagepng($im, $textImage);
        imagedestroy($im);

        $outputPath = $filePath . '.proc_' . uniqid() . '.mp4';
        
        // Prepare bufsize as a valid ffmpeg size string (e.g. "800k" -> "1600k") to avoid non-numeric PHP warnings.
        $bufsizeStr = $this->doubleBitrateSuffix($targetBitrate);

        // Enhanced ffmpeg command with aggressive compression + scaling for large resolutions + watermark
        // Scale down videos wider than 1920px to save space, maintain aspect ratio
        $cmd = escapeshellcmd($ffmpegBin)
            . ' -i ' . escapeshellarg($filePath)
            . ' -i ' . escapeshellarg($textImage)
            . ' -filter_complex "[0:v]scale=\'min(1920,iw)\':-2[scaled];[scaled][1:v]overlay=10:10[out]"'
            . ' -map "[out]" -map 0:a?'
            . ' -c:v libx264 -crf ' . intval($crf) . ' -preset medium -b:v ' . escapeshellarg($targetBitrate) . ' -maxrate ' . escapeshellarg($targetBitrate) . ' -bufsize ' . escapeshellarg($bufsizeStr)
            . ' -c:a aac -b:a 64k -movflags +faststart '
            . escapeshellarg($outputPath) . ' -y';

        $out = []; $ret = 0;
        exec($cmd . ' 2>&1', $out, $ret);
        @unlink($textImage);

        if ($ret === 0 && file_exists($outputPath)) {
            $outputSize = @filesize($outputPath) ?: 0;
            Log::info('processVideo: success', [
                'path' => $filePath,
                'original_size' => $originalSize,
                'output_size' => $outputSize,
                'compression_ratio' => $originalSize > 0 ? round(($outputSize / $originalSize) * 100, 2) . '%' : 'N/A'
            ]);
            @unlink($filePath);
            if (!@rename($outputPath, $filePath)) { @copy($outputPath, $filePath); @unlink($outputPath); }
            @chmod($filePath, 0644);
        } else {
            Log::error('processVideo: ffmpeg failed', [
                'path' => $filePath,
                'ffmpeg_bin' => $ffmpegBin,
                'return_code' => $ret,
                'output' => implode("\n", $out),
            ]);
            if (file_exists($outputPath)) @unlink($outputPath);
        }
    }

    /**
     * Attempt to double a bitrate string while preserving suffix (k/M).
     * Examples:
     *  - "800k" => "1600k"
     *  - "1M"   => "2M"
     *  - "500"  => "1000k" (assumes numeric -> k)
     *
     * This prevents numeric-arithmetic against strings like "800k" which
     * triggers "A non-numeric value encountered" warnings in PHP.
     *
     * @param string $bitrate
     * @return string
     */
    private function doubleBitrateSuffix(string $bitrate): string
    {
        $b = trim($bitrate);
        if ($b === '') {
            return '1600k';
        }

        // Match e.g. 800k, 1M, 500K, 2m
        if (preg_match('/^(\d+(?:\.\d+)?)([kKmM])$/', $b, $m)) {
            $num = (float) $m[1];
            $suffix = strtolower($m[2]);
            // Multiply by 2
            $doubled = $num * 2;
            // If decimal but effectively integer, cast to int
            if (intval($doubled) == $doubled) {
                $doubled = intval($doubled);
            }
            return $doubled . $suffix;
        }

        // If numeric only (e.g. "800"), assume it's kilobits and double to k
        if (is_numeric($b)) {
            $num = (int) $b;
            return ($num * 2) . 'k';
        }

        // Fallback: try to extract leading number
        if (preg_match('/^(\d+)/', $b, $m2)) {
            $num = (int) $m2[1];
            return ($num * 2) . 'k';
        }

        // As an ultimate fallback, return original repeated (not ideal but safe)
        return $b;
    }

    private function addTimestampToPdf($pdfPath, $timestampData)
    {
        $this->createMetadataFile($pdfPath, $timestampData);
    }

    private function addTimestampToDocument($docPath, $timestampData)
    {
        $this->createMetadataFile($docPath, $timestampData);
    }

    private function createMetadataFile($filePath, $timestampData)
    {
        $metadataPath = $filePath . '.metadata.txt';
        $content = "CHECK CALL METADATA\n";
        $content .= "==================\n";
        $content .= "Time: " . $timestampData['time'] . "\n";
        $content .= "Employee: " . $timestampData['employee'] . "\n";
        $content .= "Latitude: " . $timestampData['latitude'] . "\n";
        $content .= "Longitude: " . $timestampData['longitude'] . "\n";
        $content .= "Site: " . $timestampData['site'] . "\n";
        $content .= "Location: " . $timestampData['location'] . "\n";
        $content .= "Original File: " . basename($filePath) . "\n";

        file_put_contents($metadataPath, $content);
    }

    /**
     * Fallback watermark renderer that prefers any available system TTF font
     * to produce a larger, readable watermark. If no TTF is available it
     * falls back to GD built-in fonts (largest size) with increased padding.
     *
     * @param resource $img
     * @param string $text
     * @param string $imagePath
     * @param string $ext
     * @return void
     */
    private function addWatermarkWithGDFont($img, $text, $imagePath, $ext)
    {
        $lines = explode("\n", $text);

        // Prefer bundled font first (project), then common system fonts
        $fontCandidates = [
            public_path('fonts/Arial.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', // Linux
            '/Library/Fonts/Arial.ttf', // macOS
            'C:\Windows\Fonts\arial.ttf', // Windows
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

        // Aim for watermark that uses a noticeable portion (~15%) of image height
        $targetPercent = 0.15; // 15% of image height
        if ($availableFont) {
            // Set font size based on image height so watermark height ~15% of image
            $fontSize = max(40, intval($imgHeight * $targetPercent));
            $lineHeight = intval($fontSize * 1.05);
            $padding = intval($fontSize * 0.35);

            // Word-wrap lines to fit inside ~90% of image width
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
                            $wrapped[] = $test; // single long word
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

            // Measure actual rect width based on wrapped content
            $rectWidth = 0;
            foreach ($wrapped as $rl) {
                $bb = imagettfbbox($fontSize, 0, $availableFont, $rl);
                $w = abs($bb[4] - $bb[0]);
                if ($w > $rectWidth) $rectWidth = $w;
            }
            $rectWidth = min(max($rectWidth, intval($imgWidth * 0.4)), $maxRectWidth - 2 * $padding);

            $rectHeight = count($wrapped) * $lineHeight + 2 * $padding;

            // Draw a large semi-transparent rectangle occupying the top-left area
            imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

            $x = $padding;
            $y = $padding + $fontSize;
            foreach ($wrapped as $rl) {
                // Single draw for thinner appearance (no bold/shadow)
                imagettftext($img, $fontSize, 0, $x, $y, $white, $availableFont, $rl);
                $y += $lineHeight;
            }
        } else {
            // No TTF found — fall back to GD built-in fonts (5 is largest)
            $font = 5;
            $fontWidth = imagefontwidth($font);
            $fontHeight = imagefontheight($font);

            // Use a large rectangle width to occupy more space even with small font
            $padding = 22;
            $rectWidth = min(intval($imgWidth * 0.9) - 2 * $padding, 1200);
            $rectHeight = count($lines) * ($fontHeight + 8) + 2 * $padding;

            imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

            // Draw each line with a single draw (thinner look) and slight transparency
            $thinWhite = imagecolorallocatealpha($img, 255, 255, 255, 10); // slightly transparent
            $y = $padding;
            foreach ($lines as $line) {
                imagestring($img, $font, $padding, $y, $line, $thinWhite);
                $y += $fontHeight + 8;
            }
        }

        // Save image according to extension
        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($img, $imagePath, 90);
        } else {
            imagepng($img, $imagePath);
        }

        imagedestroy($img);
    }
    // 19. Complete Check Call (Phone-based)
    public function phoneComplete(Request $request)
    {
        $request->validate([
            'guard_id' => 'required|exists:users,id',
            'phone_number' => 'required|string',
            'timestamp' => 'nullable|date',
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
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id'        => 'required|exists:shift_dates,id',
            'name'            => 'required|string|max:255',
            // Full datetime (dd-MM-yyyy HH:mm:ss after frontend normalization → Y-m-d H:i:s)
            'scheduled_time'  => 'required|string',
            'status'          => 'required|in:pending,completed,missed',
            'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);

        // Normalize the scheduled time from any of the accepted formats to Y-m-d H:i:s.
        $raw = $request->input('scheduled_time');
        $formats = [
            'Y-m-d H:i:s', 'Y-m-d H:i',
            'd-m-Y H:i:s', 'd-m-Y H:i',
            'd/m/Y H:i:s', 'd/m/Y H:i',
        ];

        $parsed = null;
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, trim($raw));
                if ($dt) { $parsed = $dt; break; }
            } catch (\Exception $e) {
                // try next format
            }
        }

        if (! $parsed) {
            return response()->json(['message' => 'The scheduled time field must be a valid datetime (dd-MM-yyyy HH:mm:ss).'], 422);
        }

        $shiftDate = ShiftDate::find($validated['shift_id']);
        $checkcall = CheckCall::create([
            'shift_id'        => $validated['shift_id'],
            'name'            => $validated['name'],
            'employee_id'     => $shiftDate->staff_id ?? null,
            'scheduled_time'  => $parsed->format('Y-m-d H:i:s'),
            'status'          => $validated['status'],
            'approval_status' => $validated['approval_status'] ?? 'pending',
        ]);

        Logger::log($checkcall, 'Created', 'CheckCall created for shift at ' . optional($checkcall->shiftDate?->shift?->site)->site_name);

        if ($checkcall->employee_id) {
            send_push_notification(
                $checkcall->employee_id,
                'New checkcall',
                'An admin has scheduled a new checkcall for you! check on your app now.',
                ['type' => 'shift', 'shiftId' => $checkcall->shift_id],
            );
        }

        return response()->json([
            'message'   => 'Check call created successfully',
            'checkcall' => $checkcall,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $checkcall = CheckCall::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string',
            // Accept either a time-only string (H:i or H:i:s) or a full datetime in common formats
            'scheduled_time' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,missed',
            'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $validated['name'];
        }
        if ($request->has('approval_status')) {
            $updateData['approval_status'] = $validated['approval_status'];
        }
        if ($request->has('scheduled_time')) {
            $raw = $request->input('scheduled_time');

            // Try a set of common input formats. If a time-only format is provided,
            // combine with the existing checkcall date or today.
            $formats = [
                'H:i', 'H:i:s',
                'Y-m-d H:i:s', 'Y-m-d H:i',
                'd-m-Y H:i:s', 'd-m-Y H:i',
                'd/m/Y H:i:s', 'd/m/Y H:i'
            ];

            $parsed = null;
            foreach ($formats as $fmt) {
                try {
                    $dt = Carbon::createFromFormat($fmt, $raw);
                    // ensure parsing consumed the whole string by re-formatting
                    if ($dt) { $parsed = $dt; break; }
                } catch (\Exception $e) {
                    // continue trying other formats
                }
            }

            if (! $parsed) {
                return response()->json(['message' => 'The scheduled time field must match H:i or a valid datetime format.'], 422);
            }

            // If input was time-only, detected formats 'H:i' or 'H:i:s', combine with date
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($raw))) {
                $date = $checkcall->date ?? Carbon::today()->toDateString();
                $timePart = $parsed->format('H:i:s');
                $updateData['scheduled_time'] = $date . ' ' . $timePart;
            } else {
                // Full datetime given — normalize to Y-m-d H:i:s
                $updateData['scheduled_time'] = $parsed->format('Y-m-d H:i:s');
            }
        }

        if ($request->has('status')) {
            $newStatus = $validated['status'];
            $updateData['status'] = $newStatus;

            // Only set when transitioning to pending
            if ($newStatus === 'pending' && $checkcall->status !== 'pending') {
                $date = $checkcall->date ?? Carbon::today()->toDateString();
                $updateData['scheduled_time'] = $date . ' ' . Carbon::now()->format('H:i:s');
            }
        }

        $checkcall->update($updateData);
        $checkcall->refresh();

        send_push_notification(
            $checkcall->employee_id,
            'Checkcall updated',
            'An admin has updated your checkcall! check on your app now.',
            ['type' => 'shift', 'shiftId' => $checkcall->shift_date_id],
        );

        return response()->json(['message' => 'Check call updated successfully', 'checkcall' => $checkcall]);
    }
        

    public function destroy($id)
    {
        $checkCall = CheckCall::findOrFail($id);
        Logger::log($checkCall, 'Deleted', 'CheckCall deleted for shift at ' . $checkCall->shiftDate->shift->site->site_name);
         
        $checkCall->delete(); 
        return response()->json(['success' => true]);
    }

    public function approve($id)
    {
        $checkcall = CheckCall::findOrFail($id);

        // Only allow approval if check call is completed
        if ($checkcall->status !== 'completed') {
            return response()->json([
                'message' => 'Only completed check calls can be approved'
            ], 400);
        }

        // Only allow approval if currently pending
        if ($checkcall->approval_status !== 'pending' && $checkcall->approval_status !== null) {
            return response()->json([
                'message' => 'Check call has already been ' . $checkcall->approval_status 
            ], 400);
        }

        $checkcall->approval_status = 'approved';
        $checkcall->save();

        // Send notification to the employee. The shift reference is the check call's
        // shift_id (the FK to shift_dates); there is no shift_date_id column.
        send_push_notification(
            $checkcall->employee_id,
            'Check Call Approved',
            'Your check call "' . $checkcall->name . '" has been approved by admin.',
            ['type' => 'shift', 'shiftId' => $checkcall->shift_id],
        );

        return response()->json([
            'message' => 'Check call approved successfully',
            'checkcall' => $checkcall
        ]);
    }

    public function reject($id)
    {
        $checkcall = CheckCall::findOrFail($id);

        // Only allow rejection if check call is completed
        if ($checkcall->status !== 'completed') {
            return response()->json([
                'message' => 'Only completed check calls can be rejected'
            ], 400);
        }

        // Only allow rejection if currently pending
        if ($checkcall->approval_status !== 'pending' && $checkcall->approval_status !== null) {
            return response()->json([
                'message' => 'Check call has already been ' . $checkcall->approval_status
            ], 400);
        }

        $checkcall->approval_status = 'rejected';
        $checkcall->status = 'pending';
        $checkcall->save();

        // Send notification to the employee. The shift reference is the check call's
        // shift_id (the FK to shift_dates); there is no shift_date_id column.
        send_push_notification(
            $checkcall->employee_id,
            'Check Call Rejected',
            'Your check call "' . $checkcall->name . '" has been rejected by admin.',
            ['type' => 'shift', 'shiftId' => $checkcall->shift_id],
        );

        return response()->json([
            'message' => 'Check call rejected successfully',
            'checkcall' => $checkcall
        ]);
    }

    private function ensureWithinShiftSiteRadius(ShiftDate $shiftDate, $guardLat, $guardLng, string $activity)
    {
        if (!(bool) ($shiftDate->shift?->restrict_location_check ?? false)) {
            return null;
        }

        $site = $shiftDate->shift?->site;

        if (!$site) {
            return response()->json([
                'message' => 'Site information is missing for this shift. Cannot verify your location.',
            ], 422);
        }

        $geoService = app(GeoService::class);

        $address  = trim((string) ($site->address ?? ''));
        $postCode = trim((string) ($site->post_code ?? ''));

        if ($address === '' && $postCode === '') {
            Log::warning('Site address and postcode both missing for geofence', [
                'shift_date_id' => $shiftDate->id,
                'site_id' => $site->id,
            ]);

            return response()->json([
                'message' => 'Site address is missing. Cannot verify your location.',
            ], 422);
        }

        Log::info('Using site address for geocoding (checkcall)', [
            'shift_date_id' => $shiftDate->id,
            'site_id' => $site->id,
            'site_address' => $address,
            'site_postcode' => $postCode,
        ]);

        $plusCode = trim((string) ($site->plus_code ?? ''));
        $siteCoords = $geoService->getCoordinatesFromAddress($address, $postCode ?: null, $plusCode ?: null);

        if (!$siteCoords || !isset($siteCoords['lat'], $siteCoords['lng'])) {
            Log::warning('Address geocoding failed for site (checkcall)', [
                'shift_date_id' => $shiftDate->id,
                'site_id' => $site->id,
                'site_address' => $address,
            ]);

            return response()->json([
                'message' => 'Unable to verify site location right now. Please try again shortly.',
            ], 422);
        }

        $distanceMeters = $geoService->distanceInMeters($guardLat, $guardLng, $siteCoords['lat'], $siteCoords['lng']);

        $allowedMeters = 1000;

        // Helpful logging for debugging radius decisions

        if ($distanceMeters > $allowedMeters) {
            return response()->json([
                'message' => 'You are outside the allowed site radius and cannot ' . $activity . '.',
                'distance_meters' => round($distanceMeters, 1),
                'allowed_radius_meters' => round($allowedMeters, 1),
                'site' => [
                    'id' => $site->id,
                    'name' => $site->site_name,
                ],
            ], 422);
        }

        return null;
    }
}