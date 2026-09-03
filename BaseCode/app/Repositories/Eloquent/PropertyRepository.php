<?php

namespace App\Repositories\Eloquent;

use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;

class PropertyRepository
{
    /**
     * Lấy tất cả property của 1 landlord
     */
    public function getByLandlordId(int $landlordId): Collection
    {
        return Property::where('landlord_id', $landlordId)
            ->with('rooms')
            ->orderBy('name')
            ->get();
    }

    /**
     * Tìm property theo ID
     */
    public function findById(int $id): ?Property
    {
        return Property::with('rooms')->find($id);
    }

    /**
     * Tạo property mới
     */
    public function create(array $data): Property
    {
        return Property::create($data);
    }

    /**
     * Cập nhật property
     */
    public function update(Property $property, array $data): bool
    {
        return $property->update($data);
    }
}
