<?php
namespace App\Repositories\Eloquent;

use App\Models\BoardingHouse;
use App\Repositories\Interfaces\BoardingHouseRepositoryInterface;

class BoardingHouseRepository implements BoardingHouseRepositoryInterface
{
    public function createBoardingHouse(array $data)
    {
        return BoardingHouse::create($data);
    }

    public function findById(int $id)
    {
        return BoardingHouse::with('user')->findOrFail($id);
    }

    public function getPendingBoardingHouses()
    {
        return BoardingHouse::with('user:id,name,email,phone')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLandlordBoardingHouses(int $userId)
    {
        return BoardingHouse::where('user_id', $userId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLandlordBoardingHousesHistory(int $userId)
    {
        return BoardingHouse::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateStatus(int $id, string $status)
    {
        return BoardingHouse::where('id', $id)->update(['status' => $status]);
    }
}
