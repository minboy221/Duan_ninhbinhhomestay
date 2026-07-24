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
}
