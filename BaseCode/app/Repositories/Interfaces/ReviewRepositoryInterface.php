<?php
namespace App\Repositories\Interfaces;

interface ReviewRepositoryInterface
{
    public function getReviewsByRoomId(int $roomId);
}
