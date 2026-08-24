<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Hiển thị trang quản lý danh mục
     * Controller chỉ nhận request và trả kết quả, logic xử lý nằm ở Service
     */
    public function index()
    {
        $data = $this->categoryService->getAllData();

        return Inertia::render('Admin/Category/index', [
            'categories' => $data['types'],
            'areas'      => $data['areas'],
            'amenities'  => $data['amenities'],
        ]);
    }

    // ========== CATEGORY (Loại phòng) ==========

    /**
     * Thêm mới danh mục loại phòng
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
        ]);

        $this->categoryService->createCategory($request->only(['name', 'icon']));

        return redirect()->back()->with('success', 'Thêm loại phòng thành công!');
    }

    /**
     * Cập nhật danh mục loại phòng
     */
    public function updateCategory(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
        ]);

        $result = $this->categoryService->updateCategory($id, $request->only(['name', 'icon']));

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy danh mục!');
        }

        return redirect()->back()->with('success', 'Cập nhật loại phòng thành công!');
    }

    /**
     * Xóa danh mục loại phòng
     */
    public function deleteCategory(int $id)
    {
        $result = $this->categoryService->deleteCategory($id);

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy danh mục!');
        }

        return redirect()->back()->with('success', 'Xóa loại phòng thành công!');
    }

    /**
     * Toggle trạng thái ẩn/hiện danh mục
     */
    public function toggleCategory(int $id)
    {
        $result = $this->categoryService->toggleCategory($id);

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy danh mục!');
        }

        return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
    }

    // ========== AREA (Khu vực) ==========

    /**
     * Thêm mới khu vực
     */
    public function storeArea(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'icon'      => 'nullable|string|max:100',
            'map_embed' => 'nullable|string',
        ]);

        $this->categoryService->createArea($request->only(['name', 'icon', 'map_embed']));

        return redirect()->back()->with('success', 'Thêm khu vực thành công!');
    }

    /**
     * Cập nhật khu vực
     */
    public function updateArea(Request $request, int $id)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'icon'      => 'nullable|string|max:100',
            'map_embed' => 'nullable|string',
        ]);

        $result = $this->categoryService->updateArea($id, $request->only(['name', 'icon', 'map_embed']));

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy khu vực!');
        }

        return redirect()->back()->with('success', 'Cập nhật khu vực thành công!');
    }

    /**
     * Xóa khu vực
     */
    public function deleteArea(int $id)
    {
        $result = $this->categoryService->deleteArea($id);

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy khu vực!');
        }

        return redirect()->back()->with('success', 'Xóa khu vực thành công!');
    }

    /**
     * Toggle trạng thái ẩn/hiện khu vực
     */
    public function toggleArea(int $id)
    {
        $result = $this->categoryService->toggleArea($id);

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy khu vực!');
        }

        return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
    }

    // ========== AMENITY (Tiện ích) ==========

    /**
     * Thêm mới tiện ích
     */
    public function storeAmenity(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:amenities,name',
            'icon' => 'nullable|string|max:100',
        ], [
            'name.unique' => 'Tiện ích này đã tồn tại trong hệ thống!',
        ]);

        $this->categoryService->createAmenity($request->only(['name', 'icon']));

        return redirect()->back()->with('success', 'Thêm tiện ích thành công!');
    }

    /**
     * Cập nhật tiện ích
     */
    public function updateAmenity(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:amenities,name,' . $id,
            'icon' => 'nullable|string|max:100',
        ], [
            'name.unique' => 'Tên tiện ích đã được sử dụng!',
        ]);

        $result = $this->categoryService->updateAmenity($id, $request->only(['name', 'icon']));

        if (!$result) {
            return redirect()->back()->with('error', 'Không tìm thấy tiện ích!');
        }

        return redirect()->back()->with('success', 'Cập nhật tiện ích thành công!');
    }

    /**
     * Xóa tiện ích
     */
    public function deleteAmenity(int $id)
    {
        try {
            $result = $this->categoryService->deleteAmenity($id);
            if (!$result) {
                return redirect()->back()->with('error', 'Không tìm thấy tiện ích!');
            }
            return redirect()->back()->with('success', 'Xóa tiện ích thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle trạng thái ẩn/hiện tiện ích
     */
    public function toggleAmenity(int $id)
    {
        try {
            $result = $this->categoryService->toggleAmenity($id);
            if (!$result) {
                return redirect()->back()->with('error', 'Không tìm thấy tiện ích!');
            }
            return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
