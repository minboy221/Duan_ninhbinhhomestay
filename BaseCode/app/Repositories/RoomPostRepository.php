<?php
namespace App\Repositories;

use App\Models\RoomPost;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\RoomPostRepositoryInterface;

class RoomPostRepository implements RoomPostRepositoryInterface
{
    public function getApprovedPost(int $id)
    {
        return RoomPost::with(['room.boardingHouse.user', 'room.services', 'landlord'])
            ->where('status', 'approved')
            ->findOrFail($id);
    }

    public function getPostForLandlord(int $id, int $landlordId)
    {
        $post = RoomPost::with(['room.boardingHouse', 'room.services'])->findOrFail($id);
        if ($post->landlord_id !== $landlordId) {
            abort(403, 'Bạn không có quyền xem chi tiết bài đăng này');
        }
        return $post;
    }

    public function checkUniqueViewExists(int $postId, ?int $userId, ?string $ipAddress): bool
    {
        $query = DB::table('room_post_views')->where('room_post_id', $postId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ipAddress)->whereNull('user_id');
        }
        return $query->exists();
    }

    public function recordUniqueView(int $postId, ?int $userId, ?string $ipAddress)
    {
        DB::table('room_post_views')->insert([
            'room_post_id' => $postId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function countUniqueViews(int $postId): int
    {
        return DB::table('room_post_views')->where('room_post_id', $postId)->count();
    }

    public function incrementViewCount(int $postId)
    {
        $post = RoomPost::findOrFail($postId);
        $post->timestamps = false;
        $post->increment('view_count');
    }
}
