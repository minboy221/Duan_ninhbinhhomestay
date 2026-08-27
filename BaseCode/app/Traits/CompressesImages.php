<?php
namespace App\Traits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait CompressesImages
{
    //nén ảnh & lưu trữ 1 file hình ảnh
    protected function compressAndStoreImage(
        UploadedFile $file,
        string $path,
        string $disk = 'r2_private',
        int $quality = 75,
        int $maxWidth = 1920
    ): string {
        $realPath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());
        // 1. Xử lý ảnh HEIC / HEIF của iPhone bằng Imagick
        if (in_array($extension, ['heic', 'heif'])) {
            if (extension_loaded('imagick') && class_exists('\Imagick')) {
                try {
                    $imagick = new \Imagick($realPath);
                    $imagick->setImageFormat('jpeg');
                    $imagick->setImageCompressionQuality($quality);
                    if ($imagick->getImageWidth() > $maxWidth) {
                        $imagick->scaleImage($maxWidth, 0);
                    }
                    $tempFilePath = sys_get_temp_dir() . '/heic_' . uniqid() . '.jpg';
                    $imagick->writeImage($tempFilePath);
                    $imagick->clear();
                    $imagick->destroy();
                    $storedPath = Storage::disk($disk)->putFile($path, new \Illuminate\Http\File($tempFilePath));
                    @unlink($tempFilePath);
                    return $storedPath;
                } catch (\Throwable $e) {
                    return $file->store($path, $disk);
                }
            }
            return $file->store($path, $disk);
        }
        // 2. Nén ảnh JPG, PNG, WEBP bằng PHP GD
        $imageInfo = @getimagesize($realPath);
        $mime = $imageInfo['mime'] ?? '';
        switch ($mime) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($realPath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($realPath);
                break;
            case 'image/webp':
                $source = @imagecreatefromwebp($realPath);
                break;
            default:
                $source = null;
        }
        if (!$source) {
            return $file->store($path, $disk);
        }
        $width = imagesx($source);
        $height = imagesy($source);
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) ($height * ($maxWidth / $width));
            $dest = imagecreatetruecolor($newWidth, $newHeight);
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
            }
            imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $dest;
        }
        $tempFilePath = sys_get_temp_dir() . '/compressed_' . uniqid() . '.jpg';
        imagejpeg($source, $tempFilePath, $quality);
        imagedestroy($source);
        $storedPath = Storage::disk($disk)->putFile($path, new \Illuminate\Http\File($tempFilePath));
        @unlink($tempFilePath);
        return $storedPath;
    }
    /**
     * Nén & lưu trữ danh sách mảng nhiều ảnh
     */
    protected function compressAndStoreMultipleImages(
        array $files,
        string $path,
        string $disk = 'r2_private',
        int $quality = 75,
        int $maxWidth = 1920
    ): array {
        $storedPaths = [];
        foreach ($files as $file) {
            if ($file && $file instanceof UploadedFile && $file->isValid()) {
                $storedPaths[] = $this->compressAndStoreImage($file, $path, $disk, $quality, $maxWidth);
            }
        }
        return $storedPaths;
    }
}

?>