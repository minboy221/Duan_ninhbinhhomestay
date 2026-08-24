<?php

namespace App\Repositories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Collection;

class AreaRepository
{
    /**
     * Lấy tất cả khu vực, sắp xếp theo thời gian tạo mới nhất
     */
    public function getAll(): Collection
    {
        return Area::orderBy('created_at', 'desc')->get();
    }

    /**
     * Lấy khu vực đang active (cho trang Client)
     */
    public function getActive(): Collection
    {
        return Area::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Tìm khu vực theo ID
     */
    public function findById(int $id): ?Area
    {
        return Area::find($id);
    }

    /**
     * Tạo mới khu vực
     */
    public function create(array $data): Area
    {
        return Area::create($data);
    }

    /**
     * Cập nhật khu vực
     */
    public function update(Area $area, array $data): bool
    {
        return $area->update($data);
    }

    /**
     * Xóa khu vực
     */
    public function delete(Area $area): bool
    {
        return $area->delete();
    }

    /**
     * Đếm số lượng khu vực
     */
    public function count(): int
    {
        return Area::count();
    }
}
