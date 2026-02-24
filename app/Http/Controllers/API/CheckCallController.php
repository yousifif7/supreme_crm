<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Helpers\Logger;
use App\Models\Employee;
use App\Models\Location;
use App\Models\CheckCall;
use App\Models\ShiftDate;
use App\Models\Notification;
use App\Models\ShiftBooking;
use App\Services\GeoService;
use Illuminate\Http\Request;
use App\Models\CheckCallMedia;
use Illuminate\Support\Facades\Log;
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

        if($checkCall->status == 'completed'){
            return response()->json(['message' => 'This CheckCall has already been completed.'], 404);
        }
        if($checkCall->status == 'missed'){
            return response()->json(['message' => 'This CheckCall has already been missed, You cannot submit unless an Admin gave permission to.'], 422);
        }
        
        if (!$employee) {
            return response()->json(['message' => 'No employee linked to this user.'], 404);
        }

        if($checkCall->require_media =='1' && (empty($data['media_files']) || count($data['media_files']) == 0)){
            return response()->json(['message' => 'This check call requires media evidence. Please attach media files before completing.'], 422);
        }

        $now = Carbon::now(); // incoming timestamp assumed UTC
        $scheduledUtc = Carbon::parse($checkCall->scheduled_time, 'UTC'); // stored in DB as UTC

        $earliest = $scheduledUtc->copy()->subMinutes(5);
        $latest   = $scheduledUtc->copy()->addMinutes(15);

        // if ($now->lt($earliest)) {
        //     return response()->json([
        //         'message' => 'Too early! Check call can only be completed 5 minutes before its due time. '
        //             . $scheduledUtc->format('Y-m-d H:i') . " (UTC). Your local time: " . $now,
        //     ], 422);
        // }

        // if ($now->gt($latest)) {
        //     $checkCall->status = 'missed';
        //     $checkCall->save();
        //     return response()->json([
        //         'message' => 'Missed! Check call can only be completed within 15 minutes after its due time. '
        //             . $scheduledUtc->format('Y-m-d H:i') . " (UTC). Your local time: " . $now,
        //     ], 422);
        // }

        // Prepare timestamp data for all file types
        $shiftdate = ShiftDate::find($checkCall->shift_id);
        if (!$shiftdate) {
            return response()->json(['message' => 'Shift date not found for this check call.'], 404);
        }

        // Ensure the guard is the assigned staff for this shift
        if ($shiftdate->staff_id !== $user->id) {
            return response()->json(['message' => 'You are not assigned to this shift and cannot complete this check call.'], 403);
        }

        // Ensure the guard has booked on for this shift (only booked-on guards can complete check calls)
        $bookedOn = ShiftBooking::where('user_id', $user->id)
            ->where('shift_id', $shiftdate->id)
            ->where(function ($q) {
                $q->where('type', 'like', '%on%')
                  ->orWhere('type', 'on')
                  ->orWhere('type', 'book_on');
            })->exists();

        if (!$bookedOn) {
            return response()->json(['message' => 'You must book on for this shift before completing the check call.'], 422);
        }
        $lat = $data['location']['latitude'];
        $lng = $data['location']['longitude'];

        // $geoFenceError = $this->ensureWithinShiftSiteRadius($shiftdate, $lat, $lng, 'complete this check call');
        // if ($geoFenceError) {
        //     return $geoFenceError;
        // }

        // Try to resolve human-readable address from coordinates (GeoService caches results)
        $geoService = new GeoService();
        $resolvedAddress = null;
        try {
            $resolvedAddress = $geoService->getAddressFromCoordinates($lat, $lng);
        } catch (\Exception $e) {
            // Fail silently; we'll fall back to site address
            Log::warning('GeoService failed: ' . $e->getMessage());
        }

        $timestampData = [
            'time' => Carbon::now()->format('Y-m-d H:i:s'),
            'employee' => $employee->fore_name . ' ' . $employee->sur_name,
            'latitude' => $lat,
            'longitude' => $lng,
            'site' => $shiftdate->shift->site->site_name ?? 'N/A',
            // Prefer geocoded address when available, otherwise fall back to site address
            'location' => $resolvedAddress ?? ($shiftdate->shift->site->address ?? 'N/A')
        ];
        // Handle media files
        // Collect processed file full paths so we can optionally return them as a download
        $processedFiles = [];
        foreach ($data['media_files'] ?? [] as $file) {
            $filePath = null;
            $originalName = null;

            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $extension;
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
                        'video/quicktime' => 'mov',
                        'application/pdf' => 'pdf',
                        'application/msword' => 'doc',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    ];
                    $extension = $extMap[$mime] ?? 'bin';
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

            $fullPath = public_path($filePath);
            $fileType = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            // Compress file based on type
            $compressedPath = $this->compressFile($fullPath, $fileType);
            if ($compressedPath && $compressedPath != $fullPath) {
                // Replace original with compressed version
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                rename($compressedPath, $fullPath);
            }

            // Handle different file types for timestamp
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

                case 'pdf':
                    $this->addTimestampToPdf($fullPath, $timestampData);
                    break;

                case 'doc':
                case 'docx':
                    $this->addTimestampToDocument($fullPath, $timestampData);
                    break;

                default:
                    // For unsupported file types, create a metadata file
                    $this->createMetadataFile($fullPath, $timestampData);
                    break;
            }

            // Save to DB
            CheckCallMedia::create([
                'check_call_id' => $checkCall->id,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'original_name' => $originalName,
                'file_size' => filesize($fullPath), // Store compressed file size
            ]);

            // After processing, include the main file and any metadata created by timestamping
            if (file_exists($fullPath)) {
                $processedFiles[] = $fullPath;
            }
            $metaPath = $fullPath . '.metadata.txt';
            if (file_exists($metaPath)) {
                $processedFiles[] = $metaPath;
            }
        }

        // Update check call - explicitly preserve scheduled_time
        $checkCall->status = 'completed';
        $checkCall->approval_status = 'pending';
        $checkCall->employee_id = $user->id;
        $checkCall->notes = $data['notes'] ?? null;
        $checkCall->completed_at = Carbon::now();
        $checkCall->save();

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

        } catch (\Exception $e) {
            Log::error('Notification failed: ' . $e->getMessage());
        }

        // Build media URL list for the response
        $mediaUrls = [];
        foreach ($processedFiles as $p) {
            $relative = ltrim(str_replace(public_path(), '', $p), '\\/');
            $mediaUrls[] = [
                'file_path' => $relative,
                'url'       => asset($relative),
            ];
        }

        return response()->json([
            'message'       => 'Check call completed successfully',
            'check_call_id' => $checkCall->id,
            'media'         => $mediaUrls,
        ], 200);
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
        // Check if FFmpeg is available
        if (!shell_exec('which ffmpeg')) {
            return $filePath;
        }

        $originalSize = filesize($filePath);
        $maxSize = 10 * 1024 * 1024; // 10MB target for videos
        $targetBitrate = '1000k'; // Adjust based on original size

        // Calculate target bitrate based on original file size
        if ($originalSize > 50 * 1024 * 1024) { // > 50MB
            $targetBitrate = '500k';
        } elseif ($originalSize > 20 * 1024 * 1024) { // > 20MB
            $targetBitrate = '800k';
        }

        $compressedPath = $filePath . '.compressed.mp4';
        $escapedInput = escapeshellarg($filePath);
        $escapedOutput = escapeshellarg($compressedPath);

        // FFmpeg command for compression
        $command = "ffmpeg -i {$escapedInput} " .
            "-c:v libx264 -crf 28 -preset medium -b:v {$targetBitrate} " .
            "-c:a aac -b:a 64k " .
            "-movflags +faststart " .
            "{$escapedOutput} 2>/dev/null";

        shell_exec($command);

        if (file_exists($compressedPath) && filesize($compressedPath) < $originalSize) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $filePath;
        }
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

        // Start font size relative to image width; allow downscaling until content fits
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
        $ffmpegPath = base_path('ffmpeg-7.0.2-amd64-static/ffmpeg');
        $ffprobePath = base_path('ffmpeg-7.0.2-amd64-static/ffprobe');

        // Normalize input path
        $videoPath = str_replace(['\\', '/'], '/', $videoPath);

        // Temporary directory
        $tempDir = base_path('public/temp_videos');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $outputPath = $videoPath . '.tmp.mp4';
       
        
        $location=$timestampData['location']['formatted_address']??''.' '.$timestampData['location']['street']??''.' '.$timestampData['location']['city']??''.' '.$timestampData['location']['country']??''.' '.$timestampData['location']['postal_code']??'';
        // Prepare overlay text
        $text = "Time: " . $timestampData['time'] .
            "\nEmployee: " . $timestampData['employee'] .
            "\nLat: " . $timestampData['latitude'] . "  " .
            "Lng: " . $timestampData['longitude'] .
            "\nSite: " . $timestampData['site'] .
            "\nLocation: " . $location;

        $text = str_replace([':', ','], '-', $text);

        // Generate text overlay PNG
        $textImage = $tempDir . '/text_overlay.png';
        $fontPath = base_path('ffmpeg/static/Roboto_Condensed-Black.ttf');
        $fontSize = 15;
        $im = imagecreatetruecolor(200, 300);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagettftext($im, $fontSize, 0, 10, 35, $white, $fontPath, $text);
        imagepng($im, $textImage);
        imagedestroy($im);

        // ✅ FIXED ffprobe command — NO spaces after `v:0`
        $cmdProbe = "\"$ffprobePath\" -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 \"$videoPath\" 2>&1";
        $dimensions = trim(shell_exec($cmdProbe));

        $rotateNeeded = false;
        $width = 0;
        $height = 0;

        // Parse dimensions safely
        if (!empty($dimensions)) {
            $parts = explode(',', $dimensions);
            if (count($parts) >= 2) {
                $width = (int)$parts[0];
                $height = (int)$parts[1];
            }
        }

        // Determine if rotation is required
        if ($width === 0 || $height === 0) {
            // ffprobe failed to detect — rotate by default
            $rotateNeeded = true;
        } elseif ($height < $width) {
            // Portrait mode → rotate
            $rotateNeeded = true;
        }

        // FFmpeg command
        if ($rotateNeeded) {
            // Rotate 90° clockwise + overlay
            $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"transpose=1,overlay=10:10\" -c:a copy \"$outputPath\" -y";
        } else {
            // Normal overlay
            $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"overlay=10:10\" -c:a copy \"$outputPath\" -y";
        }

        // Execute FFmpeg
        exec($cmd . ' 2>&1', $outputLines, $returnVar);

        if ($returnVar === 0 && file_exists($outputPath)) {
            unlink($videoPath);
            rename($outputPath, $videoPath);
            unlink($textImage);
           /* echo "width:  $width , height: $height";
            echo "Video processed successfully!";*/
        } else {
            echo "Error processing video! width:  $width , height: $height <br><pre>" . implode("\n", $outputLines) . "</pre>";
            echo "<br><b>Probe:</b> $cmdProbe";
            echo "<br><b>Dimensions:</b> $dimensions";
            echo "<br><b>Command:</b> $cmd";
        }
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

        // Send notification to the employee
        send_push_notification(
            $checkcall->employee_id,
            'Check Call Approved',
            'Your check call "' . $checkcall->name . '" has been approved by admin.',
            ['type' => 'shift', 'shiftId' => $checkcall->shift_date_id],
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

        // Send notification to the employee
        send_push_notification(
            $checkcall->employee_id,
            'Check Call Rejected',
            'Your check call "' . $checkcall->name . '" has been rejected by admin.',
            ['type' => 'shift', 'shiftId' => $checkcall->shift_date_id],
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

        // Use the site's plain `address` field for geocoding (postcode can be inaccurate).
        $address = trim((string) ($site->address ?? ''));

        if ($address === '') {
            Log::warning('Site address missing for geofence', [
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
        ]);

        $siteCoords = $geoService->getCoordinatesFromAddress($address, null);

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
        $baseRadius = (float) config('services.site_geofence.radius_meters', 300);
        $margin = (float) config('services.site_geofence.margin_meters', 100);
        $allowedMeters = $baseRadius + $margin;

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
