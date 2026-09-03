<?php
namespace App\Repositories\Eloquent;

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
            ->whereHas('tenant', function ($q) {
                $q->where('role', '!=', 'admin');
            })
            // Mặc dù hơi khó để join trực tiếp trong query without joining rooms,
            // nhưng ta có thể tạm chấp nhận lọc admin là đủ tốt (vì hiếm khi chủ trọ đi đánh giá phòng khác).
            // Hoặc chúng ta có thể lọc các đánh giá mà tenant_id trùng với landlord_id của boarding_house:
            ->whereNotIn('reviews.id', function ($q) {
                $q->select('reviews.id')
                  ->from('reviews')
                  ->join('rooms', 'reviews.room_id', '=', 'rooms.id')
                  ->join('boarding_houses', 'rooms.boarding_house_id', '=', 'boarding_houses.id')
                  ->whereColumn('reviews.tenant_id', 'boarding_houses.user_id');
            })
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
