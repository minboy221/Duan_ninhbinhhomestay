<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    /**
     * Lấy tất cả danh mục, sắp xếp theo thời gian tạo mới nhất
     */
    public function getAll(): Collection
    {
        return Category::orderBy('created_at', 'desc')->get();
    }

    /**
     * Lấy danh mục đang active (cho trang Client)
     */
    public function getActive(): Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Tìm danh mục theo ID
     */
    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    /**
     * Tạo mới danh mục
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Cập nhật danh mục
     */
    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    /**
     * Xóa danh mục
     */
    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    /**
     * Đếm số lượng danh mục
     */
    public function count(): int
    {
        return Category::count();
    }
}
