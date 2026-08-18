<?php

namespace App\Repositories\Interfaces;

interface TenantRepositoryInterface
{
    /**
     * Lấy danh sách khách thuê của một chủ trọ, có thể lọc theo cơ sở trọ
     *
     * @param int $landlordId
     * @param int|null $boardingHouseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTenantsByLandlord(int $landlordId, ?int $boardingHouseId = null);
}
