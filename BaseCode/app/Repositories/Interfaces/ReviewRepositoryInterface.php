<?php
namespace App\Repositories\Interfaces;

interface ReviewRepositoryInterface
{
    public function getReviewsByRoomId(int $roomId);
    public function getTopReviews(int $limit = 6);
}
