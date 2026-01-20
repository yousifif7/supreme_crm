<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BookingMedia;
use App\Models\ShiftDate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\GeoService;

class BookingMediaController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'shift_date_id' => 'required|integer',
            'type' => 'required|in:book_on,book_off',
            'media_files' => 'nullable|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
        ]);

        $user = Auth::user();

        $shiftDate = ShiftDate::find($data['shift_date_id']);
        if (!$shiftDate) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        // Resolve provided coordinates to a human-readable address (cached by GeoService).
        $lat = $request->input('location.latitude');
        $lng = $request->input('location.longitude');
        $resolvedLocation = null;
        if (!empty($lat) && !empty($lng)) {
            try {
                $geoService = new GeoService();
                $geoResult = $geoService->getAddressFromCoordinates($lat, $lng);
                if ($geoResult) {
                    $resolvedLocation = $geoResult;
                }
            } catch (\Exception $e) {
                Log::warning('GeoService failed in BookingMediaController: ' . $e->getMessage());
            }
        }
        // Fallback to site address or lat/lng string
        if (!$resolvedLocation) {
            $resolvedLocation = $shiftDate->shift->site->address ?? (is_numeric($lat) && is_numeric($lng) ? trim($lat . "," . $lng) : null);
        }

        // Save each file (supports UploadedFile instances and base64 data URLs)
        $saved = [];
        foreach ($data['media_files'] ?? [] as $file) {
            $filePath = null;
            $originalName = null;

            try {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $file->move(public_path('bookings'), $filename);
                    $filePath = 'bookings/' . $filename;
                } elseif (is_string($file) && preg_match('/^data:/', $file)) {
                    $fileData = preg_replace('/^data:\w+\/\w+;base64,/', '', $file);
                    $extension = 'png';
                    if (preg_match('/^data:(\w+\/\w+);base64,/', $file, $m)) {
                        $mime = $m[1];
                        $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','video/mp4'=>'mp4'];
                        $extension = $map[$mime] ?? 'bin';
                    }
                    if (!file_exists(public_path('bookings'))) {
                        mkdir(public_path('bookings'), 0755, true);
                    }
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    file_put_contents(public_path('bookings/' . $filename), base64_decode($fileData));
                    $filePath = 'bookings/' . $filename;
                    $originalName = $filename;
                } else {
                    continue;
                }

                $fullPath = public_path($filePath);
                $fileType = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                // Prepare timestamp data for watermarking
                $timestampData = [
                    'time' => Carbon::now()->format('Y-m-d H:i:s'),
                    'employee' => ($user->first_name ?? null) || ($user->name ?? null) ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? $user->name ?? '')) : null,
                    'latitude' => is_numeric($lat) ? (float) $lat : ($request->input('location.latitude') ?? null),
                    'longitude' => is_numeric($lng) ? (float) $lng : ($request->input('location.longitude') ?? null),
                    'site' => $shiftDate->shift->site->site_name ?? null,
                    'location' => $resolvedLocation,
                ];

                // Compress file if necessary
                $compressedPath = $this->compressFile($fullPath, $fileType);
                if ($compressedPath && $compressedPath != $fullPath) {
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    @rename($compressedPath, $fullPath);
                }

                // Apply watermark / timestamp depending on file type
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
                        $this->createMetadataFile($fullPath, $timestampData);
                        break;
                }

                $bm = BookingMedia::create([
                    'user_id' => $user->id ?? null,
                    'shift_date_id' => $shiftDate->id,
                    'type' => $data['type'],
                    'file_path' => $filePath,
                    'original_name' => $originalName,
                    'file_type' => $fileType,
                    'file_size' => file_exists($fullPath) ? filesize($fullPath) : null,
                ]);

                $saved[] = $bm;
            } catch (\Exception $e) {
                Log::error('BookingMedia store failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Files uploaded',
            'saved' => $saved
        ], 201);
    }

    // Compression and watermark methods (based on CheckCallController implementations)
    private function compressFile($filePath, $fileType)
    {
        $originalSize = @filesize($filePath) ?: 0;
        $maxSize = 5 * 1024 * 1024; // 5MB limit

        if ($originalSize <= $maxSize) {
            return $filePath; // No compression needed
        }

        switch ($fileType) {
            case 'jpg':
            case 'jpeg':
                return $this->compressImage($filePath, 60, 1920);
            case 'png':
                return $this->compressImage($filePath, 8, 1920);
            case 'mp4':
            case 'mov':
            case 'avi':
                return $this->compressVideo($filePath);
            case 'pdf':
                return $this->compressPdf($filePath);
            default:
                return $filePath;
        }
    }

    private function compressImage($filePath, $quality, $maxWidth = 1920)
    {
        $img = null;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($filePath);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($filePath);
        } else {
            return $filePath;
        }

        if (!$img) return $filePath;

        $originalWidth = imagesx($img);
        $originalHeight = imagesy($img);

        if ($originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = intval($originalHeight * $maxWidth / $originalWidth);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $newImg = imagecreatetruecolor($newWidth, $newHeight);

        if ($ext === 'png') {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        $compressedPath = $filePath . '.compressed.' . $ext;

        if ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($newImg, $compressedPath, $quality);
        } elseif ($ext === 'png') {
            imagepng($newImg, $compressedPath, $quality);
        }

        imagedestroy($img);
        imagedestroy($newImg);

        if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) {
                @unlink($compressedPath);
            }
            return $filePath;
        }
    }

    private function compressVideo($filePath)
    {
        if (!function_exists('shell_exec')) return $filePath;
        $originalSize = @filesize($filePath) ?: 0;
        $compressedPath = $filePath . '.compressed.mp4';

        $escapedInput = escapeshellarg($filePath);
        $escapedOutput = escapeshellarg($compressedPath);
        $command = "ffmpeg -i {$escapedInput} -c:v libx264 -crf 28 -preset medium -c:a aac -b:a 64k -movflags +faststart {$escapedOutput} 2>/dev/null";
        shell_exec($command);

        if (file_exists($compressedPath) && filesize($compressedPath) < $originalSize) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) @unlink($compressedPath);
            return $filePath;
        }
    }

    private function compressPdf($filePath)
    {
        if (!function_exists('shell_exec')) return $filePath;
        $compressedPath = $filePath . '.compressed.pdf';
        $escapedInput = escapeshellarg($filePath);
        $escapedOutput = escapeshellarg($compressedPath);
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$escapedOutput} {$escapedInput} 2>/dev/null";
        shell_exec($command);
        if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
            return $compressedPath;
        } else {
            if (file_exists($compressedPath)) @unlink($compressedPath);
            return $filePath;
        }
    }

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

        $locationText = is_array($timestampData['location'] ?? null) ? ($timestampData['location']['formatted_address'] ?? json_encode($timestampData['location'])) : ($timestampData['location'] ?? 'Unknown');

        $text = "Time: " . ($timestampData['time'] ?? '') .
            "\nEmployee: " . ($timestampData['employee'] ?? '') .
            "\nLat: " . ($timestampData['latitude'] ?? '') . "  " .
            "Lng: " . ($timestampData['longitude'] ?? '') .
            "\nSite: " . ($timestampData['site'] ?? '') .
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

    private function addTimestampToVideo($videoPath, $timestampData)
    {
        $ffmpegPath = base_path('ffmpeg-7.0.2-amd64-static/ffmpeg');
        $ffprobePath = base_path('ffmpeg-7.0.2-amd64-static/ffprobe');

        $videoPath = str_replace(['\\', '/'], '/', $videoPath);
        $tempDir = base_path('public/temp_videos');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $outputPath = $videoPath . '.tmp.mp4';
        $location = ($timestampData['location']['formatted_address'] ?? '') . ' ' . ($timestampData['location']['street'] ?? '') . ' ' . ($timestampData['location']['city'] ?? '') . ' ' . ($timestampData['location']['country'] ?? '') . ' ' . ($timestampData['location']['postal_code'] ?? '');
        $text = "Time: " . $timestampData['time'] . "\nEmployee: " . $timestampData['employee'] . "\nLat: " . $timestampData['latitude'] . "  " . "Lng: " . $timestampData['longitude'] . "\nSite: " . $timestampData['site'] . "\nLocation: " . $location;
        $text = str_replace([':', ','], '-', $text);

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

        $cmdProbe = "\"$ffprobePath\" -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 \"$videoPath\" 2>&1";
        $dimensions = trim(shell_exec($cmdProbe));

        $rotateNeeded = false;
        $width = 0;
        $height = 0;

        if (!empty($dimensions)) {
            $parts = explode(',', $dimensions);
            if (count($parts) >= 2) {
                $width = (int)$parts[0];
                $height = (int)$parts[1];
            }
        }

        if ($width === 0 || $height === 0) {
            $rotateNeeded = true;
        } elseif ($height < $width) {
            $rotateNeeded = true;
        }

        if ($rotateNeeded) {
            $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"transpose=1,overlay=10:10\" -c:a copy \"$outputPath\" -y";
        } else {
            $cmd = "\"$ffmpegPath\" -i \"$videoPath\" -i \"$textImage\" -filter_complex \"overlay=10:10\" -c:a copy \"$outputPath\" -y";
        }

        exec($cmd . ' 2>&1', $outputLines, $returnVar);

        if ($returnVar === 0 && file_exists($outputPath)) {
            @unlink($videoPath);
            @rename($outputPath, $videoPath);
            @unlink($textImage);
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
        $content = "BOOKING MEDIA METADATA\n";
        $content .= "==================\n";
        $content .= "Time: " . ($timestampData['time'] ?? '') . "\n";
        $content .= "Employee: " . ($timestampData['employee'] ?? '') . "\n";
        $content .= "Latitude: " . ($timestampData['latitude'] ?? '') . "\n";
        $content .= "Longitude: " . ($timestampData['longitude'] ?? '') . "\n";
        $content .= "Site: " . ($timestampData['site'] ?? '') . "\n";
        $content .= "Location: " . ($timestampData['location'] ?? '') . "\n";
        $content .= "Original File: " . basename($filePath) . "\n";

        file_put_contents($metadataPath, $content);
    }

    private function addWatermarkWithGDFont($img, $text, $imagePath, $ext)
    {
        $lines = explode("\n", $text);

        $fontCandidates = [
            public_path('fonts/Arial.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/Library/Fonts/Arial.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
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
            $fontWidth = imagefontwidth($font);
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
    }
}
