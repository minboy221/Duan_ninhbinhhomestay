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
use App\Notifications\SubscriptionNotification;
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
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $data = $request->only('name', 'phone', 'email', 'bank_name', 'bank_account_no', 'bank_account_name');

        if ($request->hasFile('avatar')) {
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Cập nhật thông tin thành công!');
    }

    public function bankSettings()
    {
        $user = Auth::user();
        return Inertia::render('Landlord/BankSettings', [
            'userData' => $user
        ]);
    }

    public function updateBankSettings(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:100',
            'bank_account_no' => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:100',
        ], [
            'bank_name.required' => 'Vui lòng chọn hoặc nhập tên ngân hàng',
            'bank_account_no.required' => 'Vui lòng nhập số tài khoản ngân hàng',
            'bank_account_name.required' => 'Vui lòng nhập tên chủ tài khoản ngân hàng',
        ]);

        $user = Auth::user();
        $user->update($request->only('bank_name', 'bank_account_no', 'bank_account_name'));

        return redirect()->back()->with('success', 'Cập nhật thông tin tài khoản ngân hàng thành công!');
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
        $allServices = $this->serviceManagementService->getServices($landlordId, $boardingHouseId);
        $services = $allServices->where('is_active', true)->values();

        //lấy tất cả tầng của chủ trọ để dùng riêng cho chọn tầng khi tạo phòng mới
        $propertyId = $this->roomService->getOrCreatePropertyId($landlordId);
        $allFloors = \App\Models\Floor::where('property_id', $propertyId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
        return Inertia::render('Landlord/Rooms/index', [
            'floors' => $floors,
            'allFloors' => $allFloors,
            'statusCounts' => $statusCounts,
            'services' => $services,
        ]);
    }

    // ========== FLOOR CRUD ==========

    public function storeFloor(Request $request)
    {
        $user = auth()->user();
        //đếm tổng số tầng hiện tại của chủ trọ
        $currentFloorCount = \App\Models\Floor::whereHas('property', function ($q) use ($user) {
            $q->where('landlord_id', $user->id);
        })->count();
        //kiểm tra với giới hạn max_properties của Gói dịch vụ
        if (!$user->canCreateResource('max_properties', $currentFloorCount)) {
            $limit = $user->getFeatureValue('max_properties');
            return redirect()->back()->with('error', "Gói dịch vụ của bạn cho phép tạo tối đa {$limit} Tầng/Dãy nhà. Bạn hiện đang có {$currentFloorCount} Tầng. Vui lòng nâng cấp gói để tạo thêm!");
        }
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
        $user = auth()->user();
        //đếm tổng số phòng hiện tại của chủ trọ
        $currentRoomCount = \App\Models\Room::whereHas('boardingHouse', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        //check với giới hạn max_rooms trong gói dịch vụ
        if (!$user->canCreateResource('max_rooms', $currentRoomCount)) {
            $limit = $user->getFeatureValue('max_rooms');
            return redirect()->back()->with(
                'error',
                "Gói dịch vụ hiện tại của bạn chỉ cho phép tối đa {$limit} phòng trọ. Vui lòng nâng cấp gói VIP để tạo thêm phòng mới!"
            );
        }
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
        $room = \App\Models\Room::findOrFail($id);
        $user = auth()->user();
        //chặn nếu phòng bị đóng băng thì không cho phép cập nhật thông tin
        if ($user->isRoomFrozen($room)) {
            return redirect()->back()->with('error', 'Phòng này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ. Vui lòng nâng cấp gói để sửa thông tin!');
        }
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
        $room = \App\Models\Room::findOrFail($id);
        $user = auth()->user();
        //chặn đổi trạng thái nếu phòng bị đóng băng
        if ($user->isRoomFrozen($room)) {
            return redirect()->back()->with(
                'error',
                'Phòng này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ.Vui lòng nâng cấp gói để thao tác!'
            );
        }
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
        $boardingHouseId = session('selected_boarding_house_id');
        $appointments = Appointment::with(['user', 'room.boardingHouse'])
            ->where('landlord_id', auth()->id())
            ->whereHas('room', function ($q) use ($boardingHouseId) {
                $q->where('boarding_house_id', $boardingHouseId); //lấy lịch hẹn của phòng thuộc cơ sở đang chọn
            })
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
                    'feedback_result' => $apt->feedback_result,
                    'has_contract' => \App\Models\Contract::where('tenant_id', $apt->user_id)->where('room_id', $apt->room_id)->exists(),
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

    public function confirmCancelAppointment(Request $request, int $id)
    {
        //yêu cầu chủ trọ nhập lý di huỷ 
        $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ], [
            'cancellation_reason.required' => 'Vui lòng nhập lý do hủy lịch hẹn!',
            'cancellation_reason.min' => 'Lý do hủy quá ngắn, vui lòng nhập tối thiểu 5 ký tự.',
        ]);

        $reason = $request->input('cancellation_reason') ?: $request->input('reason', 'Chủ trọ đã hủy lịch hẹn');

        $appointment = Appointment::where('landlord_id', Auth::id())->findOrFail($id);
        //cập nhật trạng thái lịch hẹn
        $appointment->update([
            'status' => 'cancelled',
            'feedback_result' => 'cancelled',
            'cancellation_reason' => $reason
        ]);

        // Mở lại phòng trọ về trạng thái trống
        if ($appointment->room) {
            $appointment->room->update(['status' => 'available']);
        }

        // Hủy bất kỳ dự thảo hợp đồng nào liên quan (Dùng whereIn chuẩn cú pháp SQL)
        \App\Models\Contract::where('room_id', $appointment->room_id)
            ->where('tenant_id', $appointment->user_id)
            ->whereIn('status', ['draft', 'awaiting_upload', 'pending', 'termination_requested'])
            ->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);

        // Gửi thông báo tới khách thuê
        if ($appointment->user) {
            $appointment->user->notify(new \App\Notifications\AppointmentStatusUpdated($appointment));
        }

        return redirect()->back()->with('success', 'Đã xác nhận hủy lịch hẹn và gửi lý do tới khách thuê thành công!');
    }

    // Phần hiển thị cấu hình giờ cho chủ trọ
    public function editAvailabilities()
    {
        //lấy danh sách các cơ sở trọ thuộc sở hữu của chủ trọ
        $boardingHouses = BoardingHouse::where('user_id', auth()->id())->get();
        //lấy ID cơ sở trọ đang chọn trong session
        $selectBoardingHouseId = session('selected_boarding_house_id') ?: ($boardingHouses->first()?->id ?? null);
        //lấy các cấu hình khung giờ rảnh để hiển thị lại trên form 
        $currentAvailabilities = LandlordAvailability::where('landlord_id', auth()->id())->get();
        return Inertia::render('Landlord/Availabilities/Edit', [
            'boardingHouses' => $boardingHouses,
            'currentAvailabilities' => $currentAvailabilities,
            'selectedBoardingHouseId' => $selectBoardingHouseId ? (int) $selectBoardingHouseId : null
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
            'cancel_after_minutes.required' => 'Vui lòng nhập thời gian tự động hủy lịch.',
            'cancel_after_minutes.integer' => 'Thời gian tự động hủy lịch phải là số phút.',
            'cancel_after_minutes.min' => 'Thời gian tự động hủy lịch hẹn phải tối thiểu là 5 phút.',
            'cancel_after_minutes.max' => 'Thời gian tự động hủy lịch hẹn không được vượt quá 1440 phút (24 giờ).',
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
        $landlordId = Auth::id();
        $boardingHousesId = session('selected_boarding_house_id');

        // Quét & cập nhật tự động trạng thái hợp đồng (expiring/expired) theo ngày hiện tại
        \App\Http\Controllers\Landlord\ContractController::scanContractStatuses($landlordId);

        $query = \App\Models\Contract::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        });
        //nếu chủ trọ chọn 1 cơ sở cụ thể trên header -> lấy hợp đồng thuộc cơ sở đó
        if ($boardingHousesId) {
            $query->whereHas('room', function ($q) use ($boardingHousesId) {
                $q->where('boarding_house_id', $boardingHousesId);
            });
        }
        $contracts = $query->with(['room.residents.user', 'tenant'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Danh sách ID phòng đã có hợp đồng còn hiệu lực / chờ upload
        $existingContractRoomIds = \App\Models\Contract::whereHas('room', function ($q) use ($boardingHousesId) {
            $q->where('boarding_house_id', $boardingHousesId);
        })
            ->whereIn('status', ['awaiting_upload', 'active', 'signed'])
            ->pluck('room_id')
            ->toArray();

        // Chỉ lấy những lịch hẹn mà:
        // 1. Trạng thái là approved, viewed hoặc waiting_contract
        // 2. Khách đã phản hồi "ƯNG" (feedback_result = 'interested' hoặc 'like')
        // 3. Phòng chưa có hợp đồng hiệu lực
        $appointments = \App\Models\Appointment::with(['user', 'room'])
            ->where('landlord_id', $landlordId)
            ->whereIn('status', ['approved', 'viewed', 'waiting_contract', 'success_matched'])
            ->whereIn('feedback_result', ['interested', 'like'])
            ->whereNotIn('room_id', $existingContractRoomIds)
            ->get();

        // Lấy danh sách Nhà trọ kèm các Tầng và Phòng trọ
        $boardingHousesQuery = \App\Models\BoardingHouse::where('user_id', $landlordId);
        if ($boardingHousesId) {
            $boardingHousesQuery->where('id', $boardingHousesId);
        }
        $boardingHouses = $boardingHousesQuery->with(['rooms.residents.user', 'floors.rooms.residents.user'])
            ->get();
        //lấy danh sách yêu cầu ở ghép đang chờ tạo hợp đồng
        $pendingRoommateRequests = \App\Models\RoommateRequest::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        })
            ->where('status', 'pending')
            ->with(['room', 'tenant'])
            ->get();
        return Inertia::render('Landlord/Contracts/index', [
            'dbContracts' => $contracts,
            'appointments' => $appointments,
            'pendingRoommateRequests' => $pendingRoommateRequests,
            'boardingHouses' => $boardingHouses,
            'selectedBoardingHouseId' => $boardingHousesId ?: ($boardingHouses->first()?->id ?? null),
            'authLandlord' => Auth::user(),
        ]);
    }

    public function invoices()
    {
        $landlordId = Auth::id();
        $boardingHouseId = session('selected_boarding_house_id');
        $baseQuery = \App\Models\Invoice::whereHas('contract.room', function ($q) use ($boardingHouseId) {
            $q->where('boarding_house_id', $boardingHouseId);
        })
            ->with(['contract.room', 'contract.tenant', 'details.service']);

        $invoices = (clone $baseQuery)->whereNull('archived_at')->orderBy('created_at', 'desc')->get();
        $archivedInvoices = (clone $baseQuery)->whereNotNull('archived_at')->orderBy('archived_at', 'desc')->get();

        $activeContracts = \App\Models\Contract::whereHas('room', function ($q) use ($boardingHouseId) {
            $q->where('boarding_house_id', $boardingHouseId);
            //lọc theo cơ sở được chọn
        })
            ->where('status', '!=', 'terminated')
            ->with([
                'room.services',
                'tenant',
                'invoices' => function ($q) {
                    $q->orderBy('billing_month', 'desc')->with('details');
                }
            ])
            ->get();

        $boardingHouses = \App\Models\BoardingHouse::where('user_id', $landlordId)
            ->where('status', 'approved')
            ->get();

        $currentMonth = date('Y-m');
        $pendingBillingContracts = \App\Models\Contract::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        })
            ->whereIn('status', ['active', 'signed'])
            ->whereDoesntHave('invoices', function ($q) use ($currentMonth) {
                $q->where('billing_month', $currentMonth);
            })
            ->with([
                'room.services',
                'tenant',
                'room.boardingHouse',
                'invoices' => function ($q) {
                    $q->orderBy('billing_month', 'desc')->with('details');
                }
            ])
            ->get();

        // Get landlord services
        $services = $this->serviceManagementService->getServices($landlordId, $boardingHouseId)
            ->where('is_active', true)
            ->values();
        return Inertia::render('Landlord/Invoices/index', [
            'invoices' => $invoices,
            'archivedInvoices' => $archivedInvoices,
            'activeContracts' => $activeContracts,
            'services' => $services,
            'boardingHouses' => $boardingHouses,
            'pendingBillingContracts' => $pendingBillingContracts,
        ]);
    }

    public function storeInvoice(Request $request)
    {
        $user = auth()->user();
        $contract = \App\Models\Contract::findOrFail($request->contract_id);
        $room = $contract->room;
        //check xem phòng  có bị đóng băng không
        if ($user->isRoomFrozen($room)) {
            return redirect()->back()->with('error', 'Phòng này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ. Vui lòng nâng cấp gói để lập hoá đơn!');
        }
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
            'elec_meter_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'elec_old_meter_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'water_meter_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'water_old_meter_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);
        $contract = \App\Models\Contract::with('room')->findOrFail($request->contract_id);

        // Kiểm tra kỳ thanh toán phải nằm trong khoảng thời hạn hợp đồng
        $startMonth = substr($contract->start_date, 0, 7);
        $endMonth = substr($contract->end_date, 0, 7);
        if ($request->billing_month < $startMonth || $request->billing_month > $endMonth) {
            return redirect()->back()->with('error', "Kỳ thanh toán phải nằm trong khoảng thời hạn hợp đồng ({$startMonth} đến {$endMonth})!");
        }

        // Ràng buộc không cho phép tạo kỳ thanh toán trước quá 1 tháng so với tháng hiện tại
        $maxAllowedMonth = \Carbon\Carbon::now()->addMonth()->format('Y-m');
        if ($request->billing_month > $maxAllowedMonth) {
            return redirect()->back()->with('error', "Kỳ thanh toán không được tạo vượt quá 1 tháng so với tháng hiện tại (Kỳ tối đa được phép: Tháng {$maxAllowedMonth})!");
        }

        // Tránh trùng hóa đơn trong cùng kỳ thanh toán
        $exists = \App\Models\Invoice::where('contract_id', $request->contract_id)
            ->where('billing_month', $request->billing_month)
            ->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Hóa đơn cho hợp đồng này trong kỳ này đã tồn tại!');
        }

        // Lấy hóa đơn kỳ trước để khóa chỉ số điện / nước cũ server-side
        $lastInv = \App\Models\Invoice::where('contract_id', $request->contract_id)
            ->orderBy('billing_month', 'desc')
            ->with('details')
            ->first();

        // Xử lý upload ảnh chỉ số công tơ
        $elecImgPath = null;
        $elecOldImgPath = null;
        if ($request->hasFile('elec_meter_image')) {
            $elecImgPath = '/storage/' . $request->file('elec_meter_image')->store('meter_readings', 'public');
        }
        if ($request->hasFile('elec_old_meter_image')) {
            $elecOldImgPath = '/storage/' . $request->file('elec_old_meter_image')->store('meter_readings', 'public');
        }

        $waterImgPath = null;
        $waterOldImgPath = null;
        if ($request->hasFile('water_meter_image')) {
            $waterImgPath = '/storage/' . $request->file('water_meter_image')->store('meter_readings', 'public');
        }
        if ($request->hasFile('water_old_meter_image')) {
            $waterOldImgPath = '/storage/' . $request->file('water_old_meter_image')->store('meter_readings', 'public');
        }

        // Khóa giá phòng chuẩn theo Hợp đồng
        $roomPrice = $contract->room ? (float) $contract->room->price : 0;

        $processedDetails = [];
        foreach ($request->details as $d) {
            $itemName = $d['item_name'];
            $price = (float) $d['price'];
            $quantity = (float) $d['quantity'];
            $oldIndex = isset($d['old_index']) ? (int) $d['old_index'] : null;
            $newIndex = isset($d['new_index']) ? (int) $d['new_index'] : null;
            $meterImg = null;
            $oldMeterImg = null;

            if ($itemName === 'Tiền thuê nhà') {
                $price = $roomPrice;
                $quantity = 1;
            } elseif (str_contains($itemName, 'Điện')) {
                if ($newIndex === null || $newIndex === '') {
                    return redirect()->back()->with('error', 'Vui lòng nhập chỉ số điện mới!');
                }
                if ($lastInv) {
                    $lastElec = $lastInv->details->first(fn($dt) => str_contains($dt->item_name, 'Điện'));
                    if ($lastElec && $lastElec->new_index !== null) {
                        $oldIndex = (int) $lastElec->new_index;
                        if (!$elecOldImgPath && $lastElec->meter_image_path) {
                            $oldMeterImg = $lastElec->meter_image_path;
                        }
                    }
                } elseif ($contract->entry_elec_index !== null) {
                    $oldIndex = (int) $contract->entry_elec_index;
                    if (!$elecOldImgPath && $contract->entry_elec_image) {
                        $oldMeterImg = $contract->entry_elec_image;
                    }
                }
                if ($newIndex < $oldIndex) {
                    return redirect()->back()->with('error', "Chỉ số điện mới ({$newIndex}) không được nhỏ hơn chỉ số cũ ({$oldIndex})!");
                }
                $quantity = $newIndex - $oldIndex;
                $meterImg = $elecImgPath;
                if ($elecOldImgPath)
                    $oldMeterImg = $elecOldImgPath;
            } elseif (str_contains($itemName, 'Nước')) {
                if ($newIndex === null || $newIndex === '') {
                    return redirect()->back()->with('error', 'Vui lòng nhập chỉ số nước mới!');
                }
                if ($lastInv) {
                    $lastWater = $lastInv->details->first(fn($dt) => str_contains($dt->item_name, 'Nước'));
                    if ($lastWater && $lastWater->new_index !== null) {
                        $oldIndex = (int) $lastWater->new_index;
                        if (!$waterOldImgPath && $lastWater->meter_image_path) {
                            $oldMeterImg = $lastWater->meter_image_path;
                        }
                    }
                } elseif ($contract->entry_water_index !== null) {
                    $oldIndex = (int) $contract->entry_water_index;
                    if (!$waterOldImgPath && $contract->entry_water_image) {
                        $oldMeterImg = $contract->entry_water_image;
                    }
                }
                if ($newIndex < $oldIndex) {
                    return redirect()->back()->with('error', "Chỉ số nước mới ({$newIndex}) không được nhỏ hơn chỉ số cũ ({$oldIndex})!");
                }
                $quantity = $newIndex - $oldIndex;
                $meterImg = $waterImgPath;
                if ($waterOldImgPath)
                    $oldMeterImg = $waterOldImgPath;
            }

            $subtotal = $price * $quantity;

            $processedDetails[] = [
                'service_id' => $d['service_id'] ?? null,
                'item_name' => $itemName,
                'old_index' => $oldIndex,
                'new_index' => $newIndex,
                'meter_image_path' => $meterImg,
                'old_meter_image_path' => $oldMeterImg,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        $totalAmount = collect($processedDetails)->sum('subtotal');

        $invoice = \App\Models\Invoice::create([
            'contract_id' => $request->contract_id,
            'invoice_code' => 'HD-' . date('Ym') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT),
            'billing_month' => $request->billing_month,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => $request->due_date,
        ]);

        foreach ($processedDetails as $pd) {
            $pd['invoice_id'] = $invoice->id;
            \App\Models\InvoiceDetail::create($pd);
        }

        // Gửi thông báo cho khách thuê
        $tenant = $contract->tenant;
        if ($tenant) {
            $tenant->notify(new \App\Notifications\NewInvoiceNotification($invoice));
        }
        //ghi log
        $isAbnormal = false;
        $reasons = [];
        //lấy ngưỡng tổng tiền hoá đơn cảnh báo cho Admin
        $maxInvoiceAmount = (float) (\App\Models\Setting::where('key', 'warning_invoice_amount')->value('value') ?? 10000000);
        if ($totalAmount > $maxInvoiceAmount) {
            $isAbnormal = true;
            $reasons[] = "Tổng hoá đơn lớn hơn ngưỡng thiết lập (" . number_format($totalAmount) . " đ/Ngưỡng: " . number_format($maxInvoiceAmount) . "đ)";
        }
        //kiểm tra lượng điện/nước tiêu thụ bất thường phòng
        foreach ($processedDetails as $pd) {
            if (str_contains($pd['item_name'], 'Điện') && $pd['quantity'] > 1000) {
                $isAbnormal = true;
                $reasons[] = "Tiêu thụ điện bất thường: " . $pd['quantity'] . "kWh";
            }
            if (str_contains($pd['item_name'], 'Nước') && $pd['quantity'] > 50) {
                $isAbnormal = true;
                $reasons[] = "Tiêu thụ Nước bất thường: " . $pd['quantity'] . "m3";
            }
        }
        //ghi log
        $action = $isAbnormal ? 'abnormal_invoice' : 'create_invoice';
        $logMesseage = "Chủ trọ" . Auth::user()->name . "Tạo hoá đơn {$invoice->invoice_code} cho phòng" . ($contract->room->room_number ?? 'N/A') . " (Kỳ: {$request->billing_month})";
        if ($isAbnormal) {
            $logMesseage .= " [CẢNH BÁO BẤT THƯỜNG]: " . implode(';', $reasons);
        } else {
            $logMesseage .= " với tổng tiền" . number_format($totalAmount) . "đ";
        }
        \App\Services\AuditLogger::log($action, $logMesseage, $isAbnormal);
        return redirect()->back()->with('success', 'Tạo hóa đơn thành công!');
    }

    public function storeQuickBulkInvoices(Request $request)
    {
        $request->validate([
            'billing_month' => 'required|string',
            'due_date' => 'required|date',
            'readings' => 'required|array',
            'readings.*.contract_id' => 'required|exists:contracts,id',
            'readings.*.elec_new' => 'required|numeric|min:0',
            'readings.*.water_new' => 'required|numeric|min:0',
        ]);

        $billingMonth = $request->billing_month;
        $dueDate = $request->due_date;

        // Ràng buộc không cho phép tạo kỳ thanh toán trước quá 1 tháng so với tháng hiện tại
        $maxAllowedMonth = \Carbon\Carbon::now()->addMonth()->format('Y-m');
        if ($billingMonth > $maxAllowedMonth) {
            return redirect()->back()->with('error', "Kỳ thanh toán không được tạo vượt quá 1 tháng so với tháng hiện tại (Kỳ tối đa được phép: Tháng {$maxAllowedMonth})!");
        }

        foreach ($request->readings as $index => $r) {
            $contractId = $r['contract_id'];
            $contract = \App\Models\Contract::with(['room.services', 'tenant'])->findOrFail($contractId);

            // Skip if invoice already exists
            $exists = \App\Models\Invoice::where('contract_id', $contractId)
                ->where('billing_month', $billingMonth)
                ->exists();
            if ($exists) {
                continue;
            }

            // Get last invoice for old indexes
            $lastInv = \App\Models\Invoice::where('contract_id', $contractId)
                ->orderBy('billing_month', 'desc')
                ->with('details')
                ->first();

            // Handle uploads
            $elecImgPath = null;
            $elecOldImgPath = null;
            $elecFileKey = "readings.{$index}.elec_image";
            if ($request->hasFile($elecFileKey)) {
                $elecImgPath = '/storage/' . $request->file($elecFileKey)->store('meter_readings', 'public');
            }

            $waterImgPath = null;
            $waterOldImgPath = null;
            $waterFileKey = "readings.{$index}.water_image";
            if ($request->hasFile($waterFileKey)) {
                $waterImgPath = '/storage/' . $request->file($waterFileKey)->store('meter_readings', 'public');
            }

            // Default rates
            $roomPrice = $contract->room ? (float) $contract->room->price : 0;
            $elecPrice = 3000;
            $waterPrice = 15000;

            // Override with room services
            if ($contract->room) {
                foreach ($contract->room->services as $service) {
                    if (str_contains(strtolower($service->name), 'điện') || $service->type === 'per_kwh') {
                        $elecPrice = (float) $service->price;
                    }
                    if (str_contains(strtolower($service->name), 'nước') || $service->type === 'per_m3') {
                        $waterPrice = (float) $service->price;
                    }
                }
            }

            $elecOld = 0;
            if ($lastInv) {
                $lastElec = $lastInv->details->first(fn($dt) => str_contains(strtolower($dt->item_name), 'điện'));
                if ($lastElec && $lastElec->new_index !== null) {
                    $elecOld = (int) $lastElec->new_index;
                    if ($lastElec->meter_image_path)
                        $elecOldImgPath = $lastElec->meter_image_path;
                }
            }

            $waterOld = 0;
            if ($lastInv) {
                $lastWater = $lastInv->details->first(fn($dt) => str_contains(strtolower($dt->item_name), 'nước'));
                if ($lastWater && $lastWater->new_index !== null) {
                    $waterOld = (int) $lastWater->new_index;
                    if ($lastWater->meter_image_path)
                        $waterOldImgPath = $lastWater->meter_image_path;
                }
            }

            if ((int) $r['elec_new'] < $elecOld) {
                return redirect()->back()->with('error', "Chỉ số điện mới của phòng " . ($contract->room ? $contract->room->room_number : $contractId) . " không được nhỏ hơn chỉ số cũ ({$elecOld})!");
            }
            if ((int) $r['water_new'] < $waterOld) {
                return redirect()->back()->with('error', "Chỉ số nước mới của phòng " . ($contract->room ? $contract->room->room_number : $contractId) . " không được nhỏ hơn chỉ số cũ ({$waterOld})!");
            }

            $elecQty = (int) $r['elec_new'] - $elecOld;
            $waterQty = (int) $r['water_new'] - $waterOld;

            $processedDetails = [];

            // 1. Rent
            $processedDetails[] = [
                'service_id' => null,
                'item_name' => 'Tiền thuê nhà',
                'old_index' => null,
                'new_index' => null,
                'meter_image_path' => null,
                'old_meter_image_path' => null,
                'quantity' => 1,
                'price' => $roomPrice,
                'subtotal' => $roomPrice,
            ];

            // 2. Elec
            $processedDetails[] = [
                'service_id' => null,
                'item_name' => 'Tiền Điện',
                'old_index' => $elecOld,
                'new_index' => (int) $r['elec_new'],
                'meter_image_path' => $elecImgPath,
                'old_meter_image_path' => $elecOldImgPath,
                'quantity' => $elecQty,
                'price' => $elecPrice,
                'subtotal' => $elecPrice * $elecQty,
            ];

            // 3. Water
            $processedDetails[] = [
                'service_id' => null,
                'item_name' => 'Tiền Nước',
                'old_index' => $waterOld,
                'new_index' => (int) $r['water_new'],
                'meter_image_path' => $waterImgPath,
                'old_meter_image_path' => $waterOldImgPath,
                'quantity' => $waterQty,
                'price' => $waterPrice,
                'subtotal' => $waterPrice * $waterQty,
            ];

            // 4. Fixed & Person services from room services
            if ($contract->room) {
                foreach ($contract->room->services as $service) {
                    if ($service->type === 'fixed') {
                        $processedDetails[] = [
                            'service_id' => $service->id,
                            'item_name' => $service->name,
                            'old_index' => null,
                            'new_index' => null,
                            'meter_image_path' => null,
                            'old_meter_image_path' => null,
                            'quantity' => 1,
                            'price' => (float) $service->price,
                            'subtotal' => (float) $service->price,
                        ];
                    } elseif ($service->type === 'per_person') {
                        $ppl = $contract->room->current_people ?: 1;
                        $processedDetails[] = [
                            'service_id' => $service->id,
                            'item_name' => $service->name,
                            'old_index' => null,
                            'new_index' => null,
                            'meter_image_path' => null,
                            'old_meter_image_path' => null,
                            'quantity' => $ppl,
                            'price' => (float) $service->price,
                            'subtotal' => (float) $service->price * $ppl,
                        ];
                    }
                }
            }

            $totalAmount = collect($processedDetails)->sum('subtotal');

            $invoice = \App\Models\Invoice::create([
                'contract_id' => $contractId,
                'invoice_code' => 'HD-' . date('Ym') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT),
                'billing_month' => $billingMonth,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'due_date' => $dueDate,
            ]);

            foreach ($processedDetails as $pd) {
                $pd['invoice_id'] = $invoice->id;
                \App\Models\InvoiceDetail::create($pd);
            }

            if ($contract->tenant) {
                $contract->tenant->notify(new \App\Notifications\NewInvoiceNotification($invoice));
            }
        }

        return redirect()->back()->with('success', 'Đã lập hóa đơn hàng loạt thành công!');
    }

    public function updateInvoice(Request $request, $id)
    {
        return redirect()->back()->with('error', 'Hóa đơn sau khi đã tạo không thể chỉnh sửa!');
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

    public function archiveInvoice($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        $invoice->update(['archived_at' => now()]);

        return redirect()->back()->with('success', 'Đã lưu trữ hóa đơn!');
    }

    public function restoreInvoice($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        $invoice->update(['archived_at' => null]);

        return redirect()->back()->with('success', 'Đã khôi phục hóa đơn!');
    }

    public function deleteInvoice($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Không thể xóa hóa đơn đã hoàn thành thanh toán! Vui lòng lưu trữ.');
        }

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
        $boardingHouseId = session('selected_boarding_house_id');
        $services = $this->serviceManagementService->getServices(Auth::id(), $boardingHouseId);
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
        $data = $request->all();
        $data['boarding_house_id'] = session('selected_boarding_house_id');
        $result = $this->serviceManagementService->createService(Auth::id(), $data);
        if (!$result)
            return redirect()->back()->with('error', 'Không thể thêm dịch vụ!');
        return redirect()->back()->with('success', 'Thêm dịch vụ thành công!');
    }

    public function updateService(Request $request, int $id)
    {
        // lấy thông tin dịch vụ cũ để so sánh
        $oldService = \App\Models\Service::find($id);
        if (!$oldService) {
            return redirect()->back->with('error', 'Không tìm thấy dịch vụ!');
        }
        $oldPrice = (float) $oldService->price;
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|string|in:per_kwh,per_m3,fixed,per_person',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        $result = $this->serviceManagementService->updateService(Auth::id(), $id, $request->all());
        if (!$result) {
            return redirect()->back()->with('error', 'Không thể cập nhật dịch vụ!');
        }
        //Logic kiểm tra giá tăng bất thường
        $newPrice = (float) 
            $request->price;
        $isAbnormal = false;
        $reason = "";

        //cảnh báo nếu giá tăng đột biến hơn 50% so với giá cũ
        if ($oldPrice > 0 && ($newPrice - $oldPrice) / $oldPrice >= 0.5) {
            $isAbnormal = true;
            $reason = "Tăng giá đột ngột hơn 50% (Từ " . number_format($oldPrice) . "đ lên " . number_format($newPrice) . "đ)";
        }

        //ngưỡng giá thay đổi bất thường
        $maxElecPrice = (float) (\App\Models\Setting::where('key', 'waring_electricity_price')->value('value') ?? 8000);
        $maxWaterPrice = (float) (\App\Models\Setting::where('key', 'warning_water_price')->value('value') ?? 40000);
        if (str_contains($request->name, 'Điện') && $newPrice > $maxElecPrice) {
            $isAbnormal = true;
            $reason = "Giá điện bất thường cao: " . number_format($newPrice) . "đ/kWh
            (Ngưỡng Admin thiết lập: " . number_format($maxElecPrice) . "đ)";
        }
        if (str_contains($request->name, 'Nước') && $newPrice > $maxWaterPrice) {
            $isAbnormal = true;
            $reason = "Giá nước bất thường cao: " . number_format($newPrice) . "đ/m3
            (Ngưỡng Admin thiết lập: " . number_format($maxWaterPrice) . "đ)";
        }
        //ghi log
        $action = $isAbnormal ? 'abnormal_service_price' : 'update_service';
        $logMessage = "Chủ trọ" . Auth::user()->name . " thay đổi giá dịch vụ ' {$request->name} '";
        if ($isAbnormal) {
            $logMessage .= " [CẢNH BÁO BẤT THƯỜNG]: {$reason}";
        } else {
            $logMessage .= " từ " . number_format($oldPrice) . " đ thành " . number_format($newPrice) . "đ";
        }
        \App\Services\AuditLogger::log(
            $action,
            $logMessage,
            $isAbnormal
        );
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

    public function updateBillingDay(Request $request, $id)
    {
        $request->validate([
            'invoice_billing_day' => 'required|integer|min:1|max:31'
        ]);

        $house = \App\Models\BoardingHouse::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $house->update([
            'invoice_billing_day' => $request->invoice_billing_day
        ]);

        return redirect()->back()->with('success', 'Cập nhật ngày chốt hóa đơn thành công!');
    }

    public function ocrMeter(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240'
        ]);

        $file = $request->file('image');
        $base64 = base64_encode(file_get_contents($file->path()));
        $mimeType = $file->getMimeType();

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'Chưa cấu hình GEMINI_API_KEY trong file .env!'], 400);
        }

        $prompt = "Hãy đọc chỉ số hiện tại trên công tơ điện/nước trong ảnh này. Chỉ trả về một số nguyên duy nhất đại diện cho chỉ số đó. Không trả thêm bất cứ chữ hay kí tự đặc biệt nào khác. Nếu không đọc được hoặc không tìm thấy công tơ, hãy trả về 'ERROR'.";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ],
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $base64
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $text = trim($text);

                $digits = preg_replace('/\D/', '', $text);

                if (str_contains(strtoupper($text), 'ERROR') || empty($digits)) {
                    return response()->json(['error' => 'AI không thể nhận dạng được số công tơ từ ảnh này. Vui lòng nhập tay hoặc chụp ảnh rõ nét hơn!'], 422);
                }

                return response()->json(['index' => (int) $digits]);
            } else {
                return response()->json(['error' => 'Lỗi kết nối dịch vụ AI (Gemini API): ' . $response->status()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Đã xảy ra lỗi khi gọi AI xử lý ảnh: ' . $e->getMessage()], 500);
        }
    }
    public function addResident(Request $request, int $roomId)
    {
        $request->validate([
            'phone' => 'required|string',
            'start_date' => 'required|date',
        ]);

        try {
            $this->roomService->addResident(Auth::id(), $roomId, $request->phone, $request->start_date);
            return redirect()->back()->with('success', 'Đã thêm thành viên ở ghép thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function removeResident(Request $request, int $roomId, int $residentId)
    {
        try {
            $this->roomService->removeResident(Auth::id(), $roomId, $residentId);
            return redirect()->back()->with('success', 'Đã xóa thành viên ở ghép khỏi phòng!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    //hàm kiểm tra gói và gửi thông báo nhắc gia hạn gói nếu còn dưới 3 ngày
    protected function checkSubscriptionExpiryWarning($user)
    {
        $activeSub = $user->activateSubscription;
        if (!$activeSub || !$activeSub->end_date)
            return;
        $daysRemaining = now()->startOfDay()->diffInDays($activeSub->end_date->startOfDay(), false);
        //nếu còn từ 1 -> 3 ngày và chưa gửi thông báo
        if ($daysRemaining >= 0 && $daysRemaining <= 3) {
            $alreadyNotified = $user->unreadNotifications()
                ->where('type', 'App\Notifications\SubscriptionNotification')
                ->where('data->title', 'Gói dịch vụ sắp hết hạn')
                ->exists();
            if (!$alreadyNotified) {
                $user->notify(new SubscriptionNotification(
                    "⚠️ Gói Dịch Vụ Sắp Hết Hạn",
                    "Gói \"{$activeSub->plan->name}\" của bạn sẽ hết hạn vào ngày {$activeSub->end_date->format('d/m/Y')} (Còn {$daysRemaining} ngày). Vui lòng gia hạn để không bị gián đoạn!",
                    route('landlord.subscriptions.index'),
                    'warning'
                ));
            }
        }
    }
}
