<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LandlordController extends Controller
{
    protected RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function dashboard()
    {
        return Inertia::render('Landlord/Dashboard');
    }

    public function profile()
    {
        return Inertia::render('Landlord/Profile');
    }

    /**
     * Trang quản lý phòng trọ — Controller chỉ gọi Service, trả kết quả
     */
    public function rooms()
    {
        $landlordId   = Auth::id();
        $floors       = $this->roomService->getFloorsWithRooms($landlordId);
        $statusCounts = $this->roomService->getStatusCounts($landlordId);

        return Inertia::render('Landlord/Rooms/index', [
            'floors'       => $floors,
            'statusCounts' => $statusCounts,
        ]);
    }

    // ========== FLOOR CRUD ==========

    public function storeFloor(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $result = $this->roomService->createFloor(Auth::id(), $request->only('name'));
        if (!$result) return redirect()->back()->with('error', 'Không thể thêm tầng!');
        return redirect()->back()->with('success', 'Thêm tầng thành công!');
    }

    public function updateFloor(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $result = $this->roomService->updateFloor(Auth::id(), $id, $request->only('name'));
        if (!$result) return redirect()->back()->with('error', 'Không thể cập nhật tầng!');
        return redirect()->back()->with('success', 'Cập nhật tầng thành công!');
    }

    public function deleteFloor(int $id)
    {
        $result = $this->roomService->deleteFloor(Auth::id(), $id);
        if (!$result) return redirect()->back()->with('error', 'Không thể xóa tầng!');
        return redirect()->back()->with('success', 'Xóa tầng thành công!');
    }

    // ========== ROOM CRUD ==========

    public function storeRoom(Request $request)
    {
        $request->validate([
            'floor_id'    => 'required|integer|exists:floors,id',
            'room_number' => 'required|string|max:255',
            'address'     => 'nullable|string|max:255',
            'price'       => 'required|numeric|min:0',
            'area'        => 'required|numeric|min:0',
            'capacity'    => 'nullable|integer|min:1',
            'status'      => 'nullable|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
            'amenities'   => 'nullable|string',
            'images'      => 'nullable|array|max:10',
            'images.*'    => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imageFiles = $request->file('images', []);
        $result = $this->roomService->createRoom(Auth::id(), $request->all(), $imageFiles);

        if (!$result) return redirect()->back()->with('error', 'Không thể thêm phòng!');
        return redirect()->back()->with('success', 'Thêm phòng thành công!');
    }

    public function updateRoom(Request $request, int $id)
    {
        $request->validate([
            'room_number'    => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'price'          => 'nullable|numeric|min:0',
            'area'           => 'nullable|numeric|min:0',
            'capacity'       => 'nullable|integer|min:1',
            'status'         => 'nullable|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
            'amenities'      => 'nullable|string',
            'new_images'     => 'nullable|array|max:10',
            'new_images.*'   => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'string',
        ]);

        $newImageFiles = $request->file('new_images', []);
        $removedImages = $request->input('removed_images', []);

        $result = $this->roomService->updateRoom(Auth::id(), $id, $request->all(), $newImageFiles, $removedImages);

        if (!$result) return redirect()->back()->with('error', 'Không thể cập nhật phòng!');
        return redirect()->back()->with('success', 'Cập nhật phòng thành công!');
    }

    public function changeRoomStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
        ]);
        $result = $this->roomService->changeStatus(Auth::id(), $id, $request->status);
        if (!$result) return redirect()->back()->with('error', 'Không thể đổi trạng thái!');
        return redirect()->back()->with('success', 'Đổi trạng thái thành công!');
    }

    public function deleteRoom(int $id)
    {
        $result = $this->roomService->deleteRoom(Auth::id(), $id);
        if (!$result) return redirect()->back()->with('error', 'Không thể xóa phòng!');
        return redirect()->back()->with('success', 'Xóa phòng thành công!');
    }

    // ========== CÁC TRANG KHÁC ==========

    public function listings()
    {
        return Inertia::render('Landlord/Listings/index');
    }

    public function listingCreate()
    {
        return Inertia::render('Landlord/Listings/Create');
    }

    public function appointments()
    {
        return Inertia::render('Landlord/Appointments/index');
    }

    public function tenants()
    {
        return Inertia::render('Landlord/Tenants/index');
    }

    public function contracts()
    {
        return Inertia::render('Landlord/Contracts/index');
    }

    public function invoices()
    {
        return Inertia::render('Landlord/Invoices/index');
    }

    public function finance()
    {
        return redirect()->route('landlord.invoices');
    }

    public function services()
    {
        return Inertia::render('Landlord/Services/index');
    }

    public function pricingSheets()
    {
        return redirect()->route('landlord.services');
    }

    public function pricingSheetsCreate()
    {
        return redirect()->route('landlord.services');
    }
}
