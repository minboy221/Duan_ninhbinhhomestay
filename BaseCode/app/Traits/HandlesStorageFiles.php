<?php
namespace App\Traits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandlesStorageFiles
{
    //upload mảng nhiều ảnh lên cloud R2
    protected function uploadImages(array $files, string $folder = 'room_posts_images'): array
    {
        $uploadedImages = [];
        $useR2 = config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret');
        $r2Url = rtrim(config('filesystems.disks.r2_public.url') ?? env('CLOUDFLARE_R2_PUBLIC_URL', ''), '/');
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                if ($useR2) {
                    try {
                        $path = $file->store($folder, 'r2_public');
                        if (!empty($r2Url) && !str_starts_with($path, 'http')) {
                            $uploadedImages[] = $r2Url . '/' . ltrim($path, '/');
                        } else {
                            $uploadedImages[] = str_starts_with($path, 'http') ? $path : '/storage/' . ltrim($path, '/');
                        }
                        continue;
                    } catch (\Throwable $e) {
                    }
                }
                try{
                    $path = $file->store($folder, 'public');
                    $uploadedImages[] = '/storage/' . ltrim($path, '/');
                }catch(\Throwable $ex){}
            }
        }
        return $uploadedImages;
    }
    //Xóa 1 file ảnh trên Cloud R2 hoặc Storage Local
    protected function deleteSingleImage (?string $url): void {
        if(empty($url)) return;
        $r2Url = rtrim(config('filesystems.disks.r2_public.url') ?? env('CLOUDFLARE_R2_PUBLIC_URL', ''),'/');
        //nếu là ảnh lưu trên cloudfare R2
         if (!empty($r2Url) && str_starts_with($url, $r2Url)) {
            $r2Path = ltrim(substr($url, strlen($r2Url)), '/');
            try {
                Storage::disk('r2_public')->delete($r2Path);
                return;
            } catch (\Throwable $e) {}
        }
         $localPath = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH) ?? $url);
        try {
            Storage::disk('public')->delete(ltrim($localPath, '/'));
        } catch (\Throwable $e) {}
    }
}

?>