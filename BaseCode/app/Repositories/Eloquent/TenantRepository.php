<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomResident;
use App\Repositories\Interfaces\TenantRepositoryInterface;
use App\Models\Contract;

class TenantRepository implements TenantRepositoryInterface
{
    //lấy toàn bộ khách hàng (người ở ghép, khách ký hợp đồng)
    public function getTenantsByLandlord(int $landlordId, ?int $boardingHouseId = null)
    {
        //lấy danh sách người ở ghép
        $residents = RoomResident::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        })->when($boardingHouseId, function ($q) use ($boardingHouseId) {
            $q->whereHas('room', function ($rq) use ($boardingHouseId) {
                $rq->where('boarding_house_id', $boardingHouseId);
            });
        })->with(['user.verification', 'room.floor', 'room.boardingHouse'])
            ->get();
        //lấy danh sách khách hàng ký hợp đồng chính
        $contracts = Contract::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        })->when($boardingHouseId, function ($q) use ($boardingHouseId) {
            $q->whereHas('room', function ($rq) use ($boardingHouseId) {
                $rq->where('boarding_house_id', $boardingHouseId);
            });
        })->with(['tenant.verification', 'room.floor', 'room.boardingHouse'])
            ->get();
        return [
            'residents' => $residents,
            'contracts' => $contracts,
        ];
    }
}
