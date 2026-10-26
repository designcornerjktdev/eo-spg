<?php

namespace App\Domain\Submissions\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded photos as resized WebP. The browser already shrinks photos before upload; this is the
 * server-side guarantee for clients that skipped it.
 */
class ImageOptimizer
{
    /** Longest side in pixels; matches resources/js/lib/image.ts. */
    public const MAX_SIDE = 1600;

    public const QUALITY = 80;

    /**
     * @return string the stored path on $disk
     */
    public function store(UploadedFile $file, string $directory, string $disk): string
    {
        $image = function_exists('imagewebp') ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;

        // GD cannot read some formats (e.g. HEIC); keep those as uploaded rather than rejecting the entry.
        if ($image === false) {
            return $file->store($directory, $disk);
        }

        $image = $this->resize($this->orient($image, $file), self::MAX_SIDE);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        $webp = (string) ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::random(40).'.webp';
        Storage::disk($disk)->put($path, $webp);

        return $path;
    }

    /**
     * Applies the camera's EXIF rotation so portrait JPEGs stay upright (needs the exif extension).
     */
    private function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $angle = match ((int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    private function resize(\GdImage $image, int $maxSide): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
        if ($resized === false) {
            return $image;
        }
        imagedestroy($image);

        return $resized;
    }
}
