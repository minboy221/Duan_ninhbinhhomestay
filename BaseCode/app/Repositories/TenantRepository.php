<?php

namespace App\Repositories;

use App\Models\RoomResident;
use App\Repositories\Interfaces\TenantRepositoryInterface;

class TenantRepository implements TenantRepositoryInterface
{
    public function getTenantsByLandlord(int $landlordId, ?int $boardingHouseId = null)
    {
        $query = RoomResident::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        });

        if ($boardingHouseId) {
            $query->whereHas('room', function ($q) use ($boardingHouseId) {
                $q->where('boarding_house_id', $boardingHouseId);
            });
        }

        return $query->with(['user.verification', 'room.floor'])->get();
    }
}
