<?php

namespace App\Repositories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class RoomRepository{
    /**
     * Lấy tất cả phòng theo property_id
     */
    public function getByPropertyId(int $propertyId): Collection
    {
        return Room::where('boarding_house_id', $propertyId)
            ->orderBy('room_number')
            ->get();
    }

    /**
     * Lấy tất cả phòng thuộc các property của 1 landlord
     */
    public function getByLandlordId(int $landlordId): Collection
    {
        return Room::whereHas('boardingHouse', function ($query) use ($landlordId) {
            $query->where('user_id', $landlordId);
        })->with('boardingHouse')->orderBy('room_number')->get();
    }

    /**
     * Tìm phòng theo ID
     */
    public function findById(int $id): ?Room
    {
        return Room::with('boardingHouse')->find($id);
    }

    /**
     * Tạo mới phòng
     */
    public function create(array $data): Room
    {
        return Room::create($data);
    }

    /**
     * Cập nhật phòng
     */
    public function update(Room $room, array $data): bool
    {
        return $room->update($data);
    }

    /**
     * Xóa phòng
     */
    public function delete(Room $room): bool
    {
        return $room->delete();
    }

    /**
     * Đếm phòng theo trạng thái của 1 landlord
     */
    public function countByStatusForLandlord(int $landlordId): array
    {
        return Room::whereHas('boardingHouse', function ($query) use ($landlordId) {
            $query->where('user_id', $landlordId);
        })
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status')
        ->toArray();
    }
}
