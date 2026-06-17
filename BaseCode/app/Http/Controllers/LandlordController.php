<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use App\Services\ServiceManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LandlordController extends Controller
{
    protected RoomService $roomService;
    protected ServiceManagementService $serviceManagementService;

    public function __construct(
        RoomService $roomService, 
        ServiceManagementService $serviceManagementService
    ) {
        $this->roomService = $roomService;
        $this->serviceManagementService = $serviceManagementService;
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
        try {
            $result = $this->roomService->deleteFloor(Auth::id(), $id);
            if (!$result) return redirect()->back()->with('error', 'Không thể xóa tầng!');
            return redirect()->back()->with('success', 'Xóa tầng thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ========== ROOM CRUD ==========

    public function storeRoom(Request $request)
    {
        $request->validate([
            'floor_id'     => 'required|integer|exists:floors,id',
            'room_numbers' => 'nullable|array',
            'room_number'  => 'nullable|string|max:255',
            'address'      => 'nullable|string|max:255',
            'price'        => 'required|numeric|min:0',
            'area'         => 'required|numeric|min:0',
            'capacity'     => 'nullable|integer|min:1',
            'status'       => 'nullable|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
            'amenities'    => 'nullable|string',
            'images'       => 'nullable|array|max:10',
            'images.*'     => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imageFiles = $request->file('images', []);

        if ($request->has('room_numbers') && is_array($request->room_numbers)) {
            $count = 0;
            foreach ($request->room_numbers as $rn) {
                $data = $request->except(['room_numbers', 'room_number']);
                $data['room_number'] = $rn;
                $this->roomService->createRoom(Auth::id(), $data, $imageFiles);
                $count++;
            }
            if ($count === 0) return redirect()->back()->with('error', 'Không có phòng nào được thêm!');
            return redirect()->back()->with('success', "Thêm thành công {$count} phòng!");
        } else {
            $result = $this->roomService->createRoom(Auth::id(), $request->all(), $imageFiles);
            if (!$result) return redirect()->back()->with('error', 'Không thể thêm phòng!');
            return redirect()->back()->with('success', 'Thêm phòng thành công!');
        }
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
            'maintenance_reason' => 'nullable|string',
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
            'maintenance_reason' => 'nullable|string',
        ]);
        $result = $this->roomService->changeStatus(Auth::id(), $id, $request->status, $request->maintenance_reason);
        if ($result === 'empty_people') return redirect()->back()->with('error', 'Không thể chuyển sang Đã Thuê vì phòng đang có 0 người!');
        if (!$result) return redirect()->back()->with('error', 'Không thể đổi trạng thái!');
        return redirect()->back()->with('success', 'Đổi trạng thái thành công!');
    }

    public function addPerson(int $id)
    {
        $result = $this->roomService->addPerson(Auth::id(), $id);
        if ($result === 'invalid_status') return redirect()->back()->with('error', 'Trạng thái phòng không cho phép thêm người!');
        if ($result === 'full') return redirect()->back()->with('error', 'Phòng đã đủ số lượng người tối đa.');
        if (!$result) return redirect()->back()->with('error', 'Không thể thêm người!');
        return redirect()->back()->with('success', 'Thêm người thành công!');
    }

    public function removePerson(int $id)
    {
        $result = $this->roomService->removePerson(Auth::id(), $id);
        if ($result === 'invalid_status') return redirect()->back()->with('error', 'Chỉ có thể bớt người ở trạng thái sắp hết hạn HĐ hoặc chờ gia hạn!');
        if ($result === 'empty') return redirect()->back()->with('error', 'Phòng hiện không có ai để bớt.');
        if (!$result) return redirect()->back()->with('error', 'Không thể bớt người!');
        return redirect()->back()->with('success', 'Bớt người thành công!');
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
        $services = $this->serviceManagementService->getServices(Auth::id());
        return Inertia::render('Landlord/Services/index', [
            'services' => $services
        ]);
    }

    public function storeService(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|string|in:per_kwh,per_m3,fixed,per_person',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $result = $this->serviceManagementService->createService(Auth::id(), $request->all());
        if (!$result) return redirect()->back()->with('error', 'Không thể thêm dịch vụ!');
        return redirect()->back()->with('success', 'Thêm dịch vụ thành công!');
    }

    public function updateService(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|string|in:per_kwh,per_m3,fixed,per_person',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $result = $this->serviceManagementService->updateService(Auth::id(), $id, $request->all());
        if (!$result) return redirect()->back()->with('error', 'Không thể cập nhật dịch vụ!');
        return redirect()->back()->with('success', 'Cập nhật dịch vụ thành công!');
    }

    public function deleteService(int $id)
    {
        $result = $this->serviceManagementService->deleteService(Auth::id(), $id);
        if (!$result) return redirect()->back()->with('error', 'Không thể xóa dịch vụ!');
        return redirect()->back()->with('success', 'Xóa dịch vụ thành công!');
    }

    public function changeServiceStatus(Request $request, int $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $result = $this->serviceManagementService->changeStatus(Auth::id(), $id, $request->is_active);
        if (!$result) return redirect()->back()->with('error', 'Không thể cập nhật trạng thái!');
        return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
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
