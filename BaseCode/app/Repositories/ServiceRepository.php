<?php

namespace App\Repositories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository
{
    /**
     * Lấy tất cả dịch vụ theo property_id
     */
    public function getByPropertyId(int $propertyId): Collection
    {
        return Service::where('property_id', $propertyId)->get();
    }

    /**
     * Tìm dịch vụ theo ID
     */
    public function findById(int $id): ?Service
    {
        return Service::with('property')->find($id);
    }

    /**
     * Tạo mới dịch vụ
     */
    public function create(array $data): Service
    {
        return Service::create($data);
    }

    /**
     * Cập nhật dịch vụ
     */
    public function update(Service $service, array $data): bool
    {
        return $service->update($data);
    }

    /**
     * Xóa dịch vụ
     */
    public function delete(Service $service): bool
    {
        return $service->delete();
    }
}
