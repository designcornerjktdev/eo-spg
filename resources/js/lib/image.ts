/** Longest side after resizing; matches App\Domain\Submissions\Services\ImageOptimizer. */
const MAX_SIDE = 1600;
const QUALITY = 0.8;

function toBlob(canvas: HTMLCanvasElement, type: string): Promise<Blob | null> {
    return new Promise((resolve) => canvas.toBlob(resolve, type, QUALITY));
}

/**
 * Shrinks a photo and re-encodes it as WebP before upload, so phones send a few hundred KB instead of several MB.
 * Falls back to JPEG on browsers that cannot encode WebP, and to the original file when it cannot be decoded (e.g. HEIC on Chrome);
 * the server converts again either way.
 */
export async function optimizeImage(file: File): Promise<File> {
    let bitmap: ImageBitmap;
    try {
        // Applies the camera's EXIF rotation, so portrait photos stay upright.
        bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    } catch {
        return file;
    }

    const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    let blob = await toBlob(canvas, 'image/webp');
    // Older Safari ignores the WebP request and returns PNG, which is larger than JPEG for photos.
    if (!blob || blob.type !== 'image/webp') {
        blob = await toBlob(canvas, 'image/jpeg');
    }
    if (!blob || blob.size >= file.size) {
        return file;
    }

    const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';
    const name = file.name.replace(/\.[^.]+$/, '') || 'photo';

    return new File([blob], `${name}.${extension}`, { type: blob.type, lastModified: Date.now() });
}
