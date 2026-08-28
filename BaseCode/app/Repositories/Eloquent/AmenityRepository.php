<?php

namespace App\Repositories\Eloquent;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Collection;

class AmenityRepository
{
    /**
     * Lấy tất cả tiện ích, sắp xếp theo thời gian tạo mới nhất
     */
    public function getAll(): Collection
    {
        return Amenity::orderBy('created_at', 'desc')->get();
    }

    /**
     * Lấy tiện ích đang active (cho trang Client)
     */
    public function getActive(): Collection
    {
        return Amenity::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();
    }

    /**
     * Tìm tiện ích theo ID
     */
    public function findById(int $id): ?Amenity
    {
        return Amenity::find($id);
    }

    /**
     * Tạo mới tiện ích
     */
    public function create(array $data): Amenity
    {
        return Amenity::create($data);
    }

    /**
     * Cập nhật tiện ích
     */
    public function update(Amenity $amenity, array $data): bool
    {
        return $amenity->update($data);
    }

    /**
     * Xóa tiện ích
     */
    public function delete(Amenity $amenity): bool
    {
        return $amenity->delete();
    }

    /**
     * Đếm số lượng tiện ích
     */
    public function count(): int
    {
        return Amenity::count();
    }
}
