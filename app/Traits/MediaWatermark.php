<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Services\GeoService;
use Illuminate\Support\Facades\Log;

/**
 * Reusable timestamp / location watermark helpers.
 *
 * Burns a "Time / Employee / Lat / Lng / Location" block onto uploaded media
 * so evidence images (check calls, DOB, incidents) all carry the same proof of
 * when and where they were captured. Images get a burned-in watermark, videos
 * get an ffmpeg overlay (skipped silently if ffmpeg is unavailable), and any
 * other file type gets a sidecar `.metadata.txt`.
 *
 * Mirrors the original CheckCallController implementation.
 */
trait MediaWatermark
{
    /**
     * Build the base watermark data (employee name, coordinates, resolved
     * address) from the authenticated user and a location array.
     *
     * @param  mixed  $user      Authenticated user (uses first_name/last_name, falls back to name)
     * @param  array  $location  Location payload with 'latitude' / 'longitude' keys
     */
    protected function buildWatermarkData($user, $location): array
    {
        $lat = $location['latitude'] ?? null;
        $lng = $location['longitude'] ?? null;

        $resolvedAddress = null;
        if ($lat !== null && $lng !== null) {
            try {
                $resolvedAddress = (new GeoService())->getAddressFromCoordinates($lat, $lng);
            } catch (\Exception $e) {
                Log::warning('MediaWatermark: GeoService failed: ' . $e->getMessage());
            }
        }

        $userName = trim((($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
        if ($userName === '') {
            $userName = trim((string) ($user->name ?? '')) ?: 'N/A';
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
     *
     * @param  string  $fullPath           Absolute path to the saved file
     * @param  array   $baseWatermarkData  Output of buildWatermarkData()
     * @param  string|null  $captureTimestamp  Optional capture time string; defaults to now (Europe/London)
     */
    protected function stampMediaFile($fullPath, array $baseWatermarkData, $captureTimestamp = null): void
    {
        if (!file_exists($fullPath)) {
            return;
        }

        $watermarkData = $baseWatermarkData;

        if ($captureTimestamp) {
            $captureTime = $this->parseWatermarkTimestamp($captureTimestamp);
            $watermarkData['time'] = $captureTime->format('d/m/Y H:i:s');
        } else {
            $watermarkData['time'] = Carbon::now('Europe/London')->format('d/m/Y H:i:s');
        }

        $fileType = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        try {
            switch ($fileType) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $this->watermarkImage($fullPath, $watermarkData);
                    break;

                case 'mp4':
                case 'mov':
                case 'avi':
                case 'mkv':
                    $this->watermarkVideo($fullPath, $watermarkData);
                    break;

                default:
                    $this->watermarkMetadataFile($fullPath, $watermarkData);
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('MediaWatermark: failed to stamp media ' . $fullPath . ': ' . $e->getMessage());
        }
    }

    /**
     * Parse a UK-based timestamp string and return a Carbon instance.
     */
    protected function parseWatermarkTimestamp($timestamp)
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
            Log::warning('MediaWatermark: failed to parse timestamp: ' . $timestamp . ' - ' . $e->getMessage());
            return Carbon::now('Europe/London');
        }
    }

    /**
     * Build the multi-line watermark text from the watermark data array.
     */
    protected function watermarkText(array $watermarkData): string
    {
        $locationText = 'Unknown';
        if (is_array($watermarkData['location'] ?? null)) {
            $locationText = $watermarkData['location']['formatted_address'] ?? json_encode($watermarkData['location']);
        } else {
            $locationText = $watermarkData['location'] ?? 'Unknown';
        }

        return "Time: " . ($watermarkData['time'] ?? '') .
            "\nEmployee: " . ($watermarkData['employee'] ?? '') .
            "\nLat: " . ($watermarkData['latitude'] ?? '') . "  " .
            "Lng: " . ($watermarkData['longitude'] ?? '') .
            "\nLocation: " . $locationText;
    }

    /**
     * Burn a timestamp/location watermark into an image (top-left block).
     */
    protected function watermarkImage($imagePath, $watermarkData)
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

        $text = $this->watermarkText($watermarkData);

        $lines = explode("\n", $text);
        $fontPath = public_path('fonts/Arial.ttf');

        if (!file_exists($fontPath)) {
            $this->watermarkImageGdFont($img, $text, $imagePath, $ext);
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

        $wrapped = [];
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

        $lineHeight = max(12, intval($fontSize * 1.18));
        $rectWidth = 0;
        foreach ($wrapped as $rl) {
            $bb = imagettfbbox($fontSize, 0, $fontPath, $rl);
            $w = abs($bb[4] - $bb[0]);
            if ($w > $rectWidth) $rectWidth = $w;
        }
        $rectWidth = min($rectWidth, $maxRectWidth);
        $rectHeight = count($wrapped) * $lineHeight + 2 * $padding;

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
     *
     * @param  \GdImage|resource  $img
     */
    protected function watermarkImageGdFont($img, $text, $imagePath, $ext)
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
    protected function watermarkVideo($videoPath, $watermarkData)
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
            Log::info('MediaWatermark: ffmpeg not found, skipping video watermark', ['path' => $videoPath]);
            return;
        }

        $outputPath = $videoPath . '.wm_' . uniqid() . '.mp4';

        $text = $this->watermarkText($watermarkData);

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
        $textImage = sys_get_temp_dir() . '/media_overlay_' . uniqid() . '.png';
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
            Log::warning('MediaWatermark: ffmpeg video watermark failed', [
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
    protected function watermarkMetadataFile($filePath, $watermarkData)
    {
        $locationText = is_array($watermarkData['location'] ?? null)
            ? json_encode($watermarkData['location'])
            : ($watermarkData['location'] ?? 'Unknown');

        $metadataPath = $filePath . '.metadata.txt';
        $content = "MEDIA METADATA\n";
        $content .= "==================\n";
        $content .= "Time: " . ($watermarkData['time'] ?? '') . "\n";
        $content .= "Employee: " . ($watermarkData['employee'] ?? '') . "\n";
        $content .= "Latitude: " . ($watermarkData['latitude'] ?? '') . "\n";
        $content .= "Longitude: " . ($watermarkData['longitude'] ?? '') . "\n";
        $content .= "Location: " . $locationText . "\n";
        $content .= "Original File: " . basename($filePath) . "\n";

        @file_put_contents($metadataPath, $content);
    }
}
