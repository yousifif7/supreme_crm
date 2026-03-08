<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FileCompressor
{
    // Target image quality for JPEG (1-100)
    protected int $jpegQuality = 75;

    // Path to ffmpeg, if available
    protected ?string $ffmpegPath = null;

    // Path to GhostScript (gs / gswin64c), if available
    protected ?string $gsPath = null;

    public function __construct()
    {
        // Try to detect ffmpeg in a safe way without relying on disabled shell functions.
        // First, allow an explicit path through env/config (recommended for shared hosts).
        $envPath = env('FFMPEG_PATH');
        if (!empty($envPath) && is_executable($envPath)) {
            $this->ffmpegPath = $envPath;
            return;
        }

        // Common unix paths
        $candidates = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/bin/ffmpeg',
        ];

        // Common Windows paths
        $candidates = array_merge($candidates, [
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
        ]);

        foreach ($candidates as $p) {
            if (file_exists($p) && is_executable($p)) {
                $this->ffmpegPath = $p;
                return;
            }
        }

        // Last-resort: if shell_exec/exec are available, try to use them quietly
        if (function_exists('shell_exec')) {
            $which = trim(@shell_exec('which ffmpeg 2>/dev/null'));
            if (!empty($which) && is_executable($which)) {
                $this->ffmpegPath = $which;
                return;
            }
        }
        if (function_exists('exec')) {
            $out = null;
            @exec('which ffmpeg 2>/dev/null', $out);
            if (!empty($out[0]) && is_executable($out[0])) {
                $this->ffmpegPath = $out[0];
                return;
            }
        }

        // if still not found, leave ffmpegPath null — video compression will be skipped

        // ── GhostScript detection (for PDF compression) ───────────────────────
        $envGs = env('GS_PATH');
        if (!empty($envGs) && is_executable($envGs)) {
            $this->gsPath = $envGs;
        } else {
            $gsCandidates = [
                // Linux / macOS
                '/usr/bin/gs',
                '/usr/local/bin/gs',
                '/bin/gs',
                // Windows (common GhostScript install paths)
                'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.01.2\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs9.56.1\\bin\\gswin32c.exe',
            ];

            foreach ($gsCandidates as $p) {
                if (file_exists($p) && is_executable($p)) {
                    $this->gsPath = $p;
                    break;
                }
            }

            // Last-resort shell lookup
            if (empty($this->gsPath) && function_exists('shell_exec')) {
                $which = trim(@shell_exec('which gs 2>/dev/null'));
                if (!empty($which) && is_executable($which)) {
                    $this->gsPath = $which;
                }
            }
            if (empty($this->gsPath) && function_exists('exec')) {
                $out = null;
                @exec('which gs 2>/dev/null', $out);
                if (!empty($out[0]) && is_executable($out[0])) {
                    $this->gsPath = $out[0];
                }
            }
        }
    }

    /**
     * Extension → MIME fallback map used when mime_content_type() is unavailable or returns a generic type.
     */
    private const EXT_MIME_MAP = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'bmp'  => 'image/bmp',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
        'avi'  => 'video/x-msvideo',
        'mkv'  => 'video/x-matroska',
        'wmv'  => 'video/x-ms-wmv',
        'pdf'  => 'application/pdf',
    ];

    /**
     * Resolve the MIME type for a file. Falls back to an extension map when the
     * fileinfo extension is absent or returns an unhelpful generic type.
     */
    private function resolveMime(string $fullPath): string
    {
        // Primary: use finfo_open (fileinfo extension) which is more reliable than mime_content_type().
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $fullPath);
                finfo_close($finfo);
                if (!empty($mime) && $mime !== 'application/octet-stream') {
                    return $mime;
                }
            }
        }

        // Secondary: mime_content_type() (uses fileinfo internally on most setups).
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($fullPath);
            if (!empty($mime) && $mime !== 'application/octet-stream') {
                return $mime;
            }
        }

        // Fallback: derive MIME from the file extension.
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        return self::EXT_MIME_MAP[$ext] ?? 'application/octet-stream';
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

        $mime = $this->resolveMime($fullPath);

        if (str_starts_with($mime, 'image/')) {
            return $this->compressImage($fullPath, $mime);
        }

        if (str_starts_with($mime, 'video/')) {
            return $this->compressVideo($fullPath);
        }

        // PDFs and other non-media files are left untouched.
        Log::info('FileCompressor: skipping compression for non-media file', ['path' => $fullPath, 'mime' => $mime]);
        return false;
    }

    /**
     * Resize a GD image resource so neither dimension exceeds $maxDimension.
     * Returns the (possibly new) resource. Caller is responsible for imagedestroy().
     */
    private function resizeImageResource($img, string $mime, int $maxDimension = 2048)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= $maxDimension && $h <= $maxDimension) {
            return $img;
        }
        $ratio = min($maxDimension / $w, $maxDimension / $h);
        $newW  = (int) round($w * $ratio);
        $newH  = (int) round($h * $ratio);
        $resized = @imagecreatetruecolor($newW, $newH);
        if ($resized === false) {
            return $img; // fall back to original size
        }
        if ($mime === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
        }
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);
        return $resized;
    }

    protected function compressImage(string $fullPath, string $mime): bool
    {
        // GD must be available for any image compression.
        if (!extension_loaded('gd')) {
            Log::info('FileCompressor: GD extension not loaded, skipping image compression', ['path' => $fullPath]);
            return false;
        }

        try {
            // Write to a temporary file first so the original is never left corrupt
            // if something fails mid-write.
            $tmpOut = tempnam(sys_get_temp_dir(), 'imgcomp-');
            if ($tmpOut === false) {
                Log::warning('FileCompressor: could not create temp file for safe image write', ['path' => $fullPath]);
                return false;
            }

            $img = null;
            $written = false;

            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
                        break;
                    }
                    $img = @imagecreatefromjpeg($fullPath);
                    if ($img === false) {
                        Log::warning('FileCompressor: could not create JPEG image resource', ['path' => $fullPath]);
                        break;
                    }
                    $img     = $this->resizeImageResource($img, $mime);
                    $written = imagejpeg($img, $tmpOut, $this->jpegQuality);
                    break;

                case 'image/png':
                    if (!function_exists('imagecreatefrompng') || !function_exists('imagepng')) {
                        break;
                    }
                    $img = @imagecreatefrompng($fullPath);
                    if ($img === false) {
                        Log::warning('FileCompressor: could not create PNG image resource', ['path' => $fullPath]);
                        break;
                    }
                    $img = $this->resizeImageResource($img, $mime);
                    // Preserve full alpha channel so transparency is not lost.
                    imagesavealpha($img, true);
                    $written = imagepng($img, $tmpOut, 6);
                    break;

                case 'image/gif':
                    if (!function_exists('imagecreatefromgif') || !function_exists('imagegif')) {
                        break;
                    }
                    $img = @imagecreatefromgif($fullPath);
                    if ($img === false) {
                        Log::warning('FileCompressor: could not create GIF image resource', ['path' => $fullPath]);
                        break;
                    }
                    $img     = $this->resizeImageResource($img, $mime);
                    $written = imagegif($img, $tmpOut);
                    break;

                default:
                    // WebP and other formats: try generic GD creation + webp output.
                    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                        $rawData = @file_get_contents($fullPath);
                        if ($rawData !== false) {
                            $img = @imagecreatefromstring($rawData);
                            if ($img !== false) {
                                $img     = $this->resizeImageResource($img, $mime);
                                $written = imagewebp($img, $tmpOut, 80);
                            }
                        }
                    }
                    if (!$written) {
                        Log::info('FileCompressor: unsupported image type for compression', ['path' => $fullPath, 'mime' => $mime]);
                    }
                    break;
            }

            if ($img !== null && $img !== false) {
                imagedestroy($img);
            }

            if (!$written || !file_exists($tmpOut) || filesize($tmpOut) === 0) {
                // Compression produced nothing useful — leave original intact.
                @unlink($tmpOut);
                return false;
            }

            // Atomically replace the original only after a successful write.
            if (!@rename($tmpOut, $fullPath)) {
                copy($tmpOut, $fullPath);
                @unlink($tmpOut);
            }

            // tempnam() creates files with 0600 perms on Linux; restore web-readable permissions.
            @chmod($fullPath, 0644);

            return true;

        } catch (\Throwable $e) {
            Log::error('FileCompressor: image compression failed', ['path' => $fullPath, 'error' => $e->getMessage()]);
            if (!empty($tmpOut)) @unlink($tmpOut);
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
            // tempnam() creates files with 0600 perms on Linux; restore web-readable permissions.
            @chmod($fullPath, 0644);
            return true;
        } catch (\Throwable $e) {
            Log::error('FileCompressor: failed to replace original video with compressed', ['error' => $e->getMessage()]);
            @unlink($tmpOut);
            return false;
        }
    }
}
