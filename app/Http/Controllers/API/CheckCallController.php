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

    // Prepare timestamp data for all file types
    $shiftdate = ShiftDate::find($checkCall->shift_id);
    $timestampData = [
        'time' => Carbon::now()->format('Y-m-d H:i:s'),
        'employee' => $employee->fore_name . ' ' . $employee->sur_name,
        'latitude' => $data['location']['latitude'],
        'longitude' => $data['location']['longitude'],
        'site' => $shiftdate->shift->site->site_name ?? 'N/A',
        'location' => $shiftdate->shift->site->address ?? 'N/A'
    ];
    // Handle media files
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
    }

    // Update check call
    $checkCall->update([
        'status' => 'completed',
        'employee_id' => $user->id,
        'notes' => $data['notes'] ?? null,
        'completed_at' => Carbon::now(),
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
        $img = imagecreatefromjpeg($imagePath);
    } elseif ($ext === 'png') {
        $img = imagecreatefrompng($imagePath);
    }

    if (!$img) return;

    $white = imagecolorallocate($img, 255, 255, 255);
    $blackTrans = imagecolorallocatealpha($img, 0, 0, 0, 80);

    $text = "Time: " . $timestampData['time'] .
        "\nEmployee: " . $timestampData['employee'] .
        "\nLat: " . $timestampData['latitude'] . "  " .
        "Lng: " . $timestampData['longitude'] .
        "\nSite: " . $timestampData['site'] .
        "\nLocation: " . $timestampData['location'];

    $lines = explode("\n", $text);
    $fontPath = public_path('fonts/Arial.ttf');
    
    if (!file_exists($fontPath)) {
        // Fallback to GD font if TTF not available
        $this->addWatermarkWithGDFont($img, $text, $imagePath, $ext);
        return;
    }

    $imgWidth = imagesx($img);
    $fontSize = max(30, intval($imgWidth * 0.025));
    $lineHeight = $fontSize + 30;
    $padding = 15;

    $rectWidth = 0;
    foreach ($lines as $line) {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $line);
        $lineWidth = abs($bbox[4] - $bbox[0]);
        if ($lineWidth > $rectWidth) {
            $rectWidth = $lineWidth;
        }
    }
    $rectHeight = count($lines) * $lineHeight + 2 * $padding;

    imagefilledrectangle($img, 0, 0, $rectWidth + 2 * $padding, $rectHeight, $blackTrans);

    $x = $padding;
    $y = $padding + $fontSize;
    foreach ($lines as $line) {
        imagettftext($img, $fontSize, 0, $x, $y, $white, $fontPath, $line);
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

    // Normalize input video path
    $videoPath = str_replace(['\\', '/'], '/', $videoPath);

    // Temp directories
    $tempDir = base_path('public/temp_videos');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    $outputPath = $videoPath . '.tmp.mp4';

    // Prepare text
    $text = "Time - {$timestampData['time']}  Employee - {$timestampData['employee']}  Location - {$timestampData['latitude']}, {$timestampData['longitude']}";
    $text = str_replace([':', ','], '-', $text);

    // Create temporary PNG with text
    $textImage = $tempDir . '/text_overlay.png';
    $fontPath = base_path('ffmpeg/static/Roboto_Condensed-Black.ttf');
    $fontSize = 24;
    $im = imagecreatetruecolor(1000, 50); // width x height
    imagesavealpha($im, true);
    $trans_color = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $trans_color);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagettftext($im, $fontSize, 0, 10, 35, $white, $fontPath, $text);
    imagepng($im, $textImage);
    imagedestroy($im);

    // FFmpeg command to overlay the PNG
    $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"overlay=10:10\" -c:a copy \"$outputPath\" -y";

    exec($cmd, $outputLines, $returnVar);


    // Debug
    // echo $cmd; exit;

    if ($returnVar === 0 && file_exists($outputPath)) {
        unlink($videoPath);
        rename($outputPath, $videoPath);
        unlink($textImage); // cleanup
        echo "Video processed successfully!";
    } else {
        echo "Error processing video!";
        echo json_encode($outputLines);
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
