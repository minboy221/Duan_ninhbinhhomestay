<?php

namespace App\Http\Controllers;

use App\Models\BoardingHouse;
use App\Models\LandlordAvailability;
use App\Services\RoomService;
use App\Services\ServiceManagementService;
use App\Models\Appointment;
use App\Notifications\AppointmentStatusUpdated;
use App\Services\PublicListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LandlordController extends Controller
{
    protected RoomService $roomService;
    protected ServiceManagementService $serviceManagementService;

    protected PublicListingService $publicListingService;
    public function __construct(
        RoomService $roomService,
        ServiceManagementService
        $serviceManagementService,
        PublicListingService $publicListingService
    ) {
        $this->roomService = $roomService;
        $this->serviceManagementService = $serviceManagementService;
        $this->publicListingService = $publicListingService;
    }

    public function dashboard()
    {
        return Inertia::render('Landlord/Dashboard');
    }

    public function profile()
    {
        $user = Auth::user()->load(['verification', 'boardingHouse']);
        return Inertia::render('Landlord/Profile', [
            'userData' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $user->update($request->only('name', 'phone', 'email', 'bank_name', 'bank_account_no', 'bank_account_name'));

        return redirect()->back()->with('success', 'Cập nhật thông tin thành công!');
    }

    /**
     * Trang quản lý phòng trọ — Controller chỉ gọi Service, trả kết quả
     */
    public function rooms()
    {
        $landlordId = Auth::id();
        $boardingHouseId = session('selected_boarding_house_id');

        $floors = $this->roomService->getFloorsWithRooms($landlordId, $boardingHouseId);
        $statusCounts = $this->roomService->getStatusCounts($landlordId, $boardingHouseId);

        // Fetch active services
        $allServices = $this->serviceManagementService->getServices($landlordId);
        $services = $allServices->where('is_active', true)->values();

        return Inertia::render('Landlord/Rooms/index', [
            'floors' => $floors,
            'statusCounts' => $statusCounts,
            'services' => $services,
        ]);
    }

    // ========== FLOOR CRUD ==========

    public function storeFloor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        $result = $this->roomService->createFloor(Auth::id(), $request->only('name', 'address', 'latitude', 'longitude'));
        if (!$result)
            return redirect()->back()->with('error', 'Không thể thêm tầng/khu!');
        return redirect()->back()->with('success', 'Thêm tầng/khu thành công!');
    }

    public function updateFloor(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        $result = $this->roomService->updateFloor(Auth::id(), $id, $request->only('name', 'address', 'latitude', 'longitude'));
        if (!$result)
            return redirect()->back()->with('error', 'Không thể cập nhật tầng/khu!');
        return redirect()->back()->with('success', 'Cập nhật tầng/khu thành công!');
    }

    public function deleteFloor(int $id)
    {
        try {
            $result = $this->roomService->deleteFloor(Auth::id(), $id);
            if (!$result)
                return redirect()->back()->with('error', 'Không thể xóa tầng!');
            return redirect()->back()->with('success', 'Xóa tầng thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ========== ROOM CRUD ==========

    public function storeRoom(Request $request)
    {
        $request->validate([
            'floor_id' => 'required|integer|exists:floors,id',
            'room_numbers' => 'nullable|array',
            'room_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'area' => 'required|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
            'amenities' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $imageFiles = $request->file('images', []);
        $boardingHouseId = session('selected_boarding_house_id');

        if ($request->has('room_numbers') && is_array($request->room_numbers)) {
            $count = 0;
            foreach ($request->room_numbers as $rn) {
                $data = $request->except(['room_numbers', 'room_number']);
                $data['room_number'] = $rn;
                $room = $this->roomService->createRoom(Auth::id(), $data, $imageFiles, $boardingHouseId);
                if ($room) {
                    $count++;
                }
            }
            if ($count === 0)
                return redirect()->back()->with('error', 'Không thể thêm bất kỳ phòng nào! Vui lòng kiểm tra lại.');
            return redirect()->back()->with('success', "Thêm thành công {$count} phòng!");
        } else {
            $result = $this->roomService->createRoom(Auth::id(), $request->all(), $imageFiles, $boardingHouseId);
            if (!$result)
                return redirect()->back()->with('error', 'Không thể thêm phòng! Số phòng có thể đã tồn tại.');
            return redirect()->back()->with('success', 'Thêm phòng thành công!');
        }
    }

    public function updateRoom(Request $request, int $id)
    {
        $request->validate([
            'room_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'area' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
            'maintenance_reason' => 'nullable|string',
            'amenities' => 'nullable|string',
            'new_images' => 'nullable|array|max:10',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $newImageFiles = $request->file('new_images', []);
        $removedImages = $request->input('removed_images', []);

        try {
            $result = $this->roomService->updateRoom(Auth::id(), $id, $request->all(), $newImageFiles, $removedImages);
            if (!$result)
                return redirect()->back()->with('error', 'Không thể cập nhật phòng');
            return redirect()->back()->with('success', 'Cập nhật phòng thành công');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function changeRoomStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:available,rented,maintenance,deposited,expiring_soon,pending_renewal,suspended,under_construction',
            'maintenance_reason' => 'nullable|string',
        ]);
        $result = $this->roomService->changeStatus(Auth::id(), $id, $request->status, $request->maintenance_reason);
        if ($result === 'empty_people')
            return redirect()->back()->with('error', 'Không thể chuyển sang Đã Thuê vì phòng đang có 0 người!');
        if (!$result)
            return redirect()->back()->with('error', 'Không thể đổi trạng thái!');
        return redirect()->back()->with('success', 'Đổi trạng thái thành công!');
    }

    public function addPerson(int $id)
    {
        $result = $this->roomService->addPerson(Auth::id(), $id);
        if ($result === 'invalid_status')
            return redirect()->back()->with('error', 'Trạng thái phòng không cho phép thêm người!');
        if ($result === 'full')
            return redirect()->back()->with('error', 'Phòng đã đủ số lượng người tối đa.');
        if (!$result)
            return redirect()->back()->with('error', 'Không thể thêm người!');
        return redirect()->back()->with('success', 'Thêm người thành công!');
    }

    public function removePerson(int $id)
    {
        $result = $this->roomService->removePerson(Auth::id(), $id);
        if ($result === 'invalid_status')
            return redirect()->back()->with('error', 'Chỉ có thể bớt người ở trạng thái sắp hết hạn HĐ hoặc chờ gia hạn!');
        if ($result === 'empty')
            return redirect()->back()->with('error', 'Phòng hiện không có ai để bớt.');
        if (!$result)
            return redirect()->back()->with('error', 'Không thể bớt người!');
        return redirect()->back()->with('success', 'Bớt người thành công!');
    }

    public function deleteRoom(int $id)
    {
        try {
            $result = $this->roomService->deleteRoom(Auth::id(), $id);
            if (!$result)
                return redirect()->back()->with('error', 'Không thể xóa phòng!');
            return redirect()->back()->with('success', 'Xóa phòng thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
        $appointments = Appointment::with(['user', 'room.boardingHouse'])
            ->where('landlord_id', auth()->id())
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get()
            ->map(function ($apt) {
                return [
                    'id' => $apt->id,
                    'name' => $apt->user ? $apt->user->name : 'Ẩn danh',
                    'phone' => $apt->user ? $apt->user->phone : '',
                    'room' => $apt->room ? $apt->room->room_number : '',
                    'date' => $apt->date,
                    'time' => substr($apt->time, 0, 5), // Giới hạn H:i
                    'status' => $apt->status,
                    'note' => $apt->note ?? '',
                ];
            });

        return Inertia::render('Landlord/Appointments/index', [
            'dbAppointments' => $appointments
        ]);
    }

    public function approveAppointment(int $id)
    {
        $appointment = Appointment::where('landlord_id', Auth::id())->findOrFail($id);
        $appointment->update(['status' => 'approved']);

        if ($appointment->user) {
            $appointment->user->notify(new AppointmentStatusUpdated($appointment));
        }

        return redirect()->back()->with('success', 'Đã duyệt lịch hẹn xem phòng.');
    }

    public function rejectAppointment(Request $request, int $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:255'
        ], [
            'cancellation_reason.required' => 'Vui lòng cung cấp lý do từ chối lịch hẹn',
            'cancellation_reason.min' => 'Lý do từ chối quá ngắn tối thiểu 10 ký tự'
        ]);
        try {
            // Gọi service để xử lý cập nhật lên DB và gửi thông báo
            $appointment = $this->publicListingService->rejectAppointmentWithReason($id, $request->cancellation_reason);

            return redirect()->back()->with('success', 'Đã từ chối lịch hẹn xem phòng và gửi cho khách');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // Phần hiển thị cấu hình giờ cho chủ trọ
    public function editAvailabilities()
    {
        //lấy danh sách các cơ sở trọ thuộc sở hữu của chủ trọ
        $boardingHouses = BoardingHouse::where('user_id', auth()->id())->get();
        //lấy các cấu hình giờ rảnh để hiển thị lại trên form
        $currentAvailabilities = LandlordAvailability::where('landlord_id', auth()->id())->get();
        //render sang trang vue
        return Inertia::render('Landlord/Availabilities/Edit', [
            'boardingHouses' => $boardingHouses,
            'currentAvailabilities' => $currentAvailabilities
        ]);
    }

    //Phần xử lý lưu cấu hình giờ hàng loạt cho cơ sở trọ
    public function storeAvailabilities(Request $request)
    {
        //validate dữ liệu từ form gửi lên
        $request->validate([
            'boarding_house_id' => 'required|exists:boarding_houses,id',
            'cancel_after_minutes' => 'required|integer|min:5|max:1440',
            'availabilities' => 'required|array',
            'availabilities.*.day_of_week' => 'required|integer|between:0,6',
            'availabilities.*.start_time' => 'required_if:availabilities.*.is_active,true|nullable|date_format:H:i',
            'availabilities.*.end_time' => 'required_if:availabilities.*.is_active,true|nullable|date_format:H:i|after:availabilities.*.start_time',
        ], [
            'availabilities.*.end_time.after' => 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu.',
            'availabilities.*.start_time.required_if' => 'Vui lòng chọn giờ bắt đầu.',
            'availabilities.*.end_time.required_if' => 'Vui lòng chọn giờ kết thúc.',
        ]);
        $landlordId = auth()->id();
        $boardingHouseId = $request->boarding_house_id;

        //cập nhật số phút tự huỷ cho mỗi cơ sở trọ
        $boardingHouse = BoardingHouse::where('id', $boardingHouseId)
            ->where('user_id', $landlordId)
            ->firstOrFail();
        $boardingHouse->update([
            'cancel_after_minutes' => $request->cancel_after_minutes
        ]);

        //xoá sạch các câu hình cũ để ghi đè câu hình mới
        LandlordAvailability::where('boarding_house_id', $boardingHouseId)
            ->where('landlord_id', $landlordId)
            ->delete();
        //Duyệt qua mảng cấu hình gửi lên vue
        foreach ($request->availabilities as $item) {
            if ($item['is_active']) {
                LandlordAvailability::create([
                    'landlord_id' => $landlordId,
                    'boarding_house_id' => $boardingHouseId,
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                ]);
            }
        }
        return redirect()->back()->with('success', 'cập nhật khung giờ cho cơ sở thành công');
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
        $landlordId = Auth::id();
        
        $invoices = \App\Models\Invoice::whereHas('contract.room.boardingHouse', function($q) use($landlordId) {
            $q->where('user_id', $landlordId);
        })
        ->with(['contract.room', 'contract.tenant', 'details.service'])
        ->orderBy('created_at', 'desc')
        ->get();

        $activeContracts = \App\Models\Contract::whereHas('room.boardingHouse', function($q) use($landlordId) {
            $q->where('user_id', $landlordId);
        })
        ->where('status', '!=', 'terminated')
        ->with(['room.services', 'tenant'])
        ->get();

        // Get landlord services
        $boardingHouse = \App\Models\BoardingHouse::where('user_id', $landlordId)->first();
        $propertyId = $boardingHouse ? $this->roomService->getOrCreatePropertyId($landlordId) : null;
        $services = $propertyId ? \App\Models\Service::where('property_id', $propertyId)->where('is_active', true)->get() : collect();

        return Inertia::render('Landlord/Invoices/index', [
            'invoices' => $invoices,
            'activeContracts' => $activeContracts,
            'services' => $services,
        ]);
    }

    public function storeInvoice(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'billing_month' => 'required|string',
            'due_date' => 'required|date',
            'details' => 'required|array',
            'details.*.item_name' => 'required|string',
            'details.*.price' => 'required|numeric',
            'details.*.quantity' => 'required|numeric',
            'details.*.subtotal' => 'required|numeric',
            'details.*.old_index' => 'nullable|integer',
            'details.*.new_index' => 'nullable|integer',
            'details.*.service_id' => 'nullable|exists:services,id',
        ]);

        $contract = \App\Models\Contract::findOrFail($request->contract_id);
        
        // Tránh trùng hóa đơn trong cùng kỳ thanh toán
        $exists = \App\Models\Invoice::where('contract_id', $request->contract_id)
            ->where('billing_month', $request->billing_month)
            ->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Hóa đơn cho hợp đồng này trong kỳ này đã tồn tại!');
        }

        $totalAmount = collect($request->details)->sum('subtotal');

        $invoice = \App\Models\Invoice::create([
            'contract_id' => $request->contract_id,
            'invoice_code' => 'HD-' . date('Ym') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT),
            'billing_month' => $request->billing_month,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => $request->due_date,
        ]);

        foreach ($request->details as $d) {
            \App\Models\InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'service_id' => $d['service_id'] ?? null,
                'item_name' => $d['item_name'],
                'old_index' => $d['old_index'] ?? null,
                'new_index' => $d['new_index'] ?? null,
                'quantity' => $d['quantity'],
                'price' => $d['price'],
                'subtotal' => $d['subtotal'],
            ]);
        }

        // Gửi thông báo cho khách thuê
        $tenant = $contract->tenant;
        if ($tenant) {
            $tenant->notify(new \App\Notifications\NewInvoiceNotification($invoice));
        }

        return redirect()->back()->with('success', 'Tạo hóa đơn thành công!');
    }

    public function updateInvoice(Request $request, $id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);

        $request->validate([
            'due_date' => 'required|date',
            'details' => 'required|array',
            'details.*.item_name' => 'required|string',
            'details.*.price' => 'required|numeric',
            'details.*.quantity' => 'required|numeric',
            'details.*.subtotal' => 'required|numeric',
            'details.*.old_index' => 'nullable|integer',
            'details.*.new_index' => 'nullable|integer',
            'details.*.service_id' => 'nullable|exists:services,id',
        ]);

        $totalAmount = collect($request->details)->sum('subtotal');

        $invoice->update([
            'total_amount' => $totalAmount,
            'due_date' => $request->due_date,
        ]);

        // Recreate details
        $invoice->details()->delete();

        foreach ($request->details as $d) {
            \App\Models\InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'service_id' => $d['service_id'] ?? null,
                'item_name' => $d['item_name'],
                'old_index' => $d['old_index'] ?? null,
                'new_index' => $d['new_index'] ?? null,
                'quantity' => $d['quantity'],
                'price' => $d['price'],
                'subtotal' => $d['subtotal'],
            ]);
        }

        return redirect()->back()->with('success', 'Cập nhật hóa đơn thành công!');
    }

    public function updateInvoiceStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:unpaid,paid'
        ]);

        $invoice = \App\Models\Invoice::findOrFail($id);
        $invoice->update([
            'status' => $request->status,
            'paid_at' => $request->status === 'paid' ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Cập nhật trạng thái hóa đơn thành công!');
    }

    public function deleteInvoice($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        $invoice->details()->delete();
        $invoice->delete();

        return redirect()->back()->with('success', 'Xóa hóa đơn thành công!');
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
        if (!$result)
            return redirect()->back()->with('error', 'Không thể thêm dịch vụ!');
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
        if (!$result)
            return redirect()->back()->with('error', 'Không thể cập nhật dịch vụ!');
        return redirect()->back()->with('success', 'Cập nhật dịch vụ thành công!');
    }

    public function deleteService(int $id)
    {
        $result = $this->serviceManagementService->deleteService(Auth::id(), $id);
        if (!$result)
            return redirect()->back()->with('error', 'Không thể xóa dịch vụ!');
        return redirect()->back()->with('success', 'Xóa dịch vụ thành công!');
    }

    public function changeServiceStatus(Request $request, int $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $result = $this->serviceManagementService->changeStatus(Auth::id(), $id, $request->is_active);
        if (!$result)
            return redirect()->back()->with('error', 'Không thể cập nhật trạng thái!');
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
