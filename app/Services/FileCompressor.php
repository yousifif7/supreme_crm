<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FileCompressor
{
    // Target image quality for JPEG (1-100)
    protected int $jpegQuality = 75;

    // Path to ffmpeg, if available
    protected ?string $ffmpegPath = null;

    public function __construct()
    {
        // try to detect ffmpeg on the system
        $this->ffmpegPath = trim(shell_exec('which ffmpeg 2>/dev/null')) ?: null;
        if (empty($this->ffmpegPath)) {
            // Windows fallback: try where
            $win = trim(shell_exec('where ffmpeg 2>NUL'));
            $this->ffmpegPath = $win ?: null;
        }
    }

    /**
     * Compress a file in-place. Images and videos will be re-encoded. Other files are left unchanged.
     * Returns true on success, false on skip/failure.
     */
    public function compress(string $fullPath): bool
    {
        if (!file_exists($fullPath)) {
            Log::warning('FileCompressor: file does not exist', ['path' => $fullPath]);
            return false;
        }

        $mime = mime_content_type($fullPath) ?: '';

        if (str_starts_with($mime, 'image/')) {
            return $this->compressImage($fullPath, $mime);
        }

        if (str_starts_with($mime, 'video/')) {
            return $this->compressVideo($fullPath);
        }

        // For other files, skip to avoid breaking serving; log for visibility
        Log::info('FileCompressor: skipping compression for non-media file', ['path' => $fullPath, 'mime' => $mime]);
        return false;
    }

    protected function compressImage(string $fullPath, string $mime): bool
    {
        try {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    $img = @imagecreatefromjpeg($fullPath);
                    if ($img === false) {
                        Log::warning('FileCompressor: could not create JPEG image resource', ['path' => $fullPath]);
                        return false;
                    }
                    imagejpeg($img, $fullPath, $this->jpegQuality);
                    imagedestroy($img);
                    return true;

                case 'image/png':
                    $img = @imagecreatefrompng($fullPath);
                    if ($img === false) {
                        Log::warning('FileCompressor: could not create PNG image resource', ['path' => $fullPath]);
                        return false;
                    }
                    // Convert PNG to JPEG to get better compression if transparent is not required
                    $convertedPath = $fullPath;
                    // If PNG has alpha channel, we will still try to save as PNG with compression level
                    imagepng($img, $convertedPath, 6);
                    imagedestroy($img);
                    return true;

                case 'image/gif':
                    $img = @imagecreatefromgif($fullPath);
                    if ($img === false) {
                        Log::warning('FileCompressor: could not create GIF image resource', ['path' => $fullPath]);
                        return false;
                    }
                    // Re-save GIF
                    imagegif($img, $fullPath);
                    imagedestroy($img);
                    return true;

                default:
                    // For other image types (webp etc) attempt GD webp save if available
                    if (function_exists('imagecreatefromstring')) {
                        $data = file_get_contents($fullPath);
                        $img = @imagecreatefromstring($data);
                        if ($img !== false && function_exists('imagewebp')) {
                            imagewebp($img, $fullPath, 80);
                            imagedestroy($img);
                            return true;
                        }
                    }
                    Log::info('FileCompressor: unsupported image type for compression', ['path' => $fullPath, 'mime' => $mime]);
                    return false;
            }
        } catch (\Throwable $e) {
            Log::error('FileCompressor: image compression failed', ['path' => $fullPath, 'error' => $e->getMessage()]);
            return false;
        }
    }

    protected function compressVideo(string $fullPath): bool
    {
        if (empty($this->ffmpegPath)) {
            Log::info('FileCompressor: ffmpeg not found, skipping video compression', ['path' => $fullPath]);
            return false;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'vid-');
        // ensure extension is mp4 for ffmpeg output
        $tmpOut = $tmp . '.mp4';

        // Basic ffmpeg re-encode with reasonable quality settings
        $cmd = escapeshellcmd($this->ffmpegPath) . ' -i ' . escapeshellarg($fullPath) . ' -vcodec libx264 -crf 24 -preset medium -acodec aac -movflags +faststart ' . escapeshellarg($tmpOut) . ' -y 2>&1';

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($tmpOut)) {
            Log::error('FileCompressor: ffmpeg failed', ['path' => $fullPath, 'cmd' => $cmd, 'return' => $returnVar, 'output' => implode("\n", $output)]);
            @unlink($tmpOut);
            return false;
        }

        // Replace original file with compressed output
        try {
            // attempt to move tmpOut over original
            if (!@rename($tmpOut, $fullPath)) {
                // fallback to copy
                copy($tmpOut, $fullPath);
                unlink($tmpOut);
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('FileCompressor: failed to replace original video with compressed', ['error' => $e->getMessage()]);
            @unlink($tmpOut);
            return false;
        }
    }
}
