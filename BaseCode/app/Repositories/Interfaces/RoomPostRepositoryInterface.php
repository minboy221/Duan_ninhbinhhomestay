<?php
namespace App\Repositories\Interfaces;

interface RoomPostRepositoryInterface
{
    public function getApprovedPost(int $id);
    public function getPostForLandlord(int $id, int $landlordId);
    public function checkUniqueViewExists(int $postId, ?int $userId, ?string $ipAddress): bool;
    public function recordUniqueView(int $postId, ?int $userId, ?string $ipAddress);
    public function countUniqueViews(int $postId): int;
    public function incrementViewCount(int $postId);
    public function getFeaturedPosts(int $limit = 6);
}
