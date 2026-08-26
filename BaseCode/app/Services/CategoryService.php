<?php

namespace App\Services;

use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\AreaRepository;
use App\Repositories\Eloquent\AmenityRepository;


class CategoryService
{
    protected CategoryRepository $categoryRepo;
    protected AreaRepository $areaRepo;
    protected AmenityRepository $amenityRepo;

    public function __construct(
        CategoryRepository $categoryRepo,
        AreaRepository $areaRepo,
        AmenityRepository $amenityRepo
    ) {
        $this->categoryRepo = $categoryRepo;
        $this->areaRepo = $areaRepo;
        $this->amenityRepo = $amenityRepo;
    }

    /**
     * Lấy tất cả dữ liệu danh mục (types, areas, amenities) cho trang quản lý
     */
    public function getAllData(): array
    {
        return [
            'types' => $this->categoryRepo->getAll(),
            'areas' => $this->areaRepo->getAll(),
            'amenities' => $this->amenityRepo->getAll(),
        ];
    }

    /**
     * Lấy dữ liệu danh mục đang active cho trang Client
     */
    public function getActiveData(): array
    {
        return [
            'types' => $this->categoryRepo->getActive(),
            'areas' => $this->areaRepo->getActive(),
            'amenities' => $this->amenityRepo->getActive(),
        ];
    }

    // ========== CATEGORY (Loại phòng) ==========

    /**
     * Tạo danh mục mới
     */
    public function createCategory(array $data)
    {
        return $this->categoryRepo->create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? 'bi-tag',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Cập nhật danh mục
     */
    public function updateCategory(int $id, array $data): bool
    {
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            return false;
        }
        return $this->categoryRepo->update($category, $data);
    }

    /**
     * Xóa danh mục
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            return false;
        }
        return $this->categoryRepo->delete($category);
    }

    /**
     * Toggle trạng thái active của danh mục
     */
    public function toggleCategory(int $id): bool
    {
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            return false;
        }
        return $this->categoryRepo->update($category, [
            'is_active' => !$category->is_active,
        ]);
    }

    // ========== AREA (Khu vực) ==========

    /**
     * Tạo khu vực mới
     */
    public function createArea(array $data)
    {
        return $this->areaRepo->create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? 'bi-geo-alt',
            'map_embed' => $data['map_embed'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Cập nhật khu vực
     */
    public function updateArea(int $id, array $data): bool
    {
        $area = $this->areaRepo->findById($id);
        if (!$area) {
            return false;
        }
        return $this->areaRepo->update($area, $data);
    }

    /**
     * Xóa khu vực
     */
    public function deleteArea(int $id): bool
    {
        $area = $this->areaRepo->findById($id);
        if (!$area) {
            return false;
        }
        return $this->areaRepo->delete($area);
    }

    /**
     * Toggle trạng thái active của khu vực
     */
    public function toggleArea(int $id): bool
    {
        $area = $this->areaRepo->findById($id);
        if (!$area) {
            return false;
        }
        return $this->areaRepo->update($area, [
            'is_active' => !$area->is_active,
        ]);
    }

    // ========== AMENITY (Tiện ích) ==========

    /**
     * Tạo tiện ích mới
     */
    public function createAmenity(array $data)
    {
        return $this->amenityRepo->create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? 'bi-star',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Cập nhật tiện ích
     */
    public function updateAmenity(int $id, array $data): bool
    {
        $amenity = $this->amenityRepo->findById($id);
        if (!$amenity) {
            return false;
        }
        return $this->amenityRepo->update($amenity, $data);
    }

    /**
     * Xóa tiện ích
     */
    public function deleteAmenity(int $id): bool
    {
        $amenity = $this->amenityRepo->findById($id);
        if (!$amenity) {
            return false;
        }

        $inUse = \App\Models\Service::where('amenity_id', $id)->exists();
        if ($inUse) {
            throw new \Exception("Tiện ích này đang được sử dụng bởi các phòng trọ/dịch vụ của chủ trọ!");
        }

        return $this->amenityRepo->delete($amenity);
    }

    /**
     * Toggle trạng thái active của tiện ích
     */
    public function toggleAmenity(int $id): bool
    {
        $amenity = $this->amenityRepo->findById($id);
        if (!$amenity) {
            return false;
        }
        return $this->amenityRepo->update($amenity, [
            'is_active' => !$amenity->is_active,
        ]);
    }
}
