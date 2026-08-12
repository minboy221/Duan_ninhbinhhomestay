<?php
namespace App\Repositories;

use App\Models\Review;
use App\Repositories\Interfaces\ReviewRepositoryInterface;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function getReviewsByRoomId(int $roomId)
    {
        return Review::with('tenant')
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTopReviews(int $limit = 6)
    {
        return Review::with('tenant')
            ->where('rating', '>=', 4)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
