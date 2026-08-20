<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Floor;
use App\Models\RoomResident;
use App\Models\User;
use App\Repositories\RoomRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\FloorRepository;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    protected RoomRepository $roomRepo;
    protected PropertyRepository $propertyRepo;
    protected FloorRepository $floorRepo;

    public function __construct(
        RoomRepository $roomRepo,
        PropertyRepository $propertyRepo,
        FloorRepository $floorRepo
    ) {
        $this->roomRepo = $roomRepo;
        $this->propertyRepo = $propertyRepo;
        $this->floorRepo = $floorRepo;
    }

    // ========== FLOOR (Tầng) ==========

    /**
     * Lấy danh sách tầng kèm phòng cho trang quản lý
     */
    public function getFloorsWithRooms(int $landlordId, ?int $boardingHouseId = null): array
    {
        $properties = $this->propertyRepo->getByLandlordId($landlordId);
        if ($properties->isEmpty())
            return [];

        // Lấy property đầu tiên (hoặc có thể mở rộng cho nhiều property)
        $property = $properties->first();
        $floors = $this->floorRepo->getByPropertyId($property->id);

        return $floors->map(function ($floor) use ($boardingHouseId) {
            $allRooms = $floor->rooms;
            //lọc phòng thuộc cở sở đang chọn
            $rooms = $allRooms;
            if ($boardingHouseId) {
                $rooms = $rooms->where('boarding_house_id', $boardingHouseId);
            }
            return [
                'id' => $floor->id,
                'name' => $floor->name,
                'address' => $floor->address,
                'latitude' => $floor->latitude,
                'longitude' => $floor->longitude,
                'rooms' => $rooms->map(fn($r) => $this->formatRoom($r))->values()->toArray(),
                'total_rooms_count' => $allRooms->count(),
            ];
        })
            //phần lọc bỏ các tầng không có phòng thuộc cơ sở trọ đang chọn
            ->filter(function ($floor) use ($boardingHouseId) {
                if ($boardingHouseId) {
                    return
                        count($floor['rooms']) > 0 || $floor['total_rooms_count'] === 0;
                }
                return true;
            })
            ->values()->toArray();
    }

    /**
     * Lấy property_id của landlord (lấy cái đầu tiên, tự tạo nếu chưa có)
     */
    public function getOrCreatePropertyId(int $landlordId): int
    {
        $properties = $this->propertyRepo->getByLandlordId($landlordId);
        if ($properties->isNotEmpty()) {
            return $properties->first()->id;
        }
        // Tạo property mặc định cho landlord
        $property = $this->propertyRepo->create([
            'landlord_id' => $landlordId,
            'name' => 'Nhà trọ chính',
            'address' => 'Chưa cập nhật',
            'city' => 'Ninh Bình',
            'type' => 'motel_room',
            'is_active' => true,
        ]);
        return $property->id;
    }

    /**
     * Thêm tầng mới
     */
    public function createFloor(int $landlordId, array $data): ?array
    {
        $propertyId = $this->getOrCreatePropertyId($landlordId);

        // Kiểm tra xem tầng với tên đã tồn tại dưới tài khoản chủ trọ
        $floor = Floor::where('property_id', $propertyId)
            ->where('name', $data['name'])
            ->first();
        if ($floor) {
            $floor->update(array_filter([
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]));
            return [
                'id' => $floor->id,
                'name' => $floor->name,
                'address' => $floor->address,
                'latitude' => $floor->latitude,
                'longitude' => $floor->longitude,
            ];
        }

        $newFloor = Floor::create([
            'property_id' => $propertyId,
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return [
            'id' => $newFloor->id,
            'name' => $newFloor->name,
            'address' => $newFloor->address,
            'latitude' => $newFloor->latitude,
            'longitude' => $newFloor->longitude,
        ];
    }

    /**
     * Sửa tên tầng
     */
    public function updateFloor(int $landlordId, int $floorId, array $data): bool
    {
        $floor = $this->floorRepo->findById($floorId);
        if (!$floor || $floor->property->landlord_id !== $landlordId)
            return false;

        // Kiểm tra trùng tên tầng
        if (isset($data['name'])) {
            $exists = Floor::where('property_id', $floor->property_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $floorId)
                ->exists();
            if ($exists)
                return false;
        }

        return $this->floorRepo->update($floor, [
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);
    }

    /**
     * Xóa tầng (kèm toàn bộ phòng bên trong)
     */
    public function deleteFloor(int $landlordId, int $floorId): bool
    {
        $floor = $this->floorRepo->findById($floorId);
        if (!$floor || $floor->property->landlord_id !== $landlordId) {
            return false;
        }
        foreach ($floor->rooms as $room) {
            //chặn nếu phòng trong tầng/dãy đang có bài đăng tin công khai trên hệ thống
            $hasActivePost = \App\Models\RoomPost::where('room_id', $room->id)
                ->whereIn('status', ['published', 'approved', 'pending'])
                ->exists();
                if($hasActivePost){
                    throw new \Exception("Không thể xoá tầng này vì phòng '{$room->name}' đang có bài đăng tin trên hệ thống. Vui lòng gỡ hoặc ẩn tin đăng trước!");
                }
            //chặn nếu trong phòng, tầng/dãy có hợp đồng thuê còn hiệu lực
            $hasActiveContract = \App\Models\Contract::where('room_id',$room->id)
            ->whereIn('status',['active','signed','awaiting_upload','pending_renewal'])
            ->exists();
            if($hasActiveContract){
                throw new \Exception("Không thể xoá tầng này vì phòng '{$room->name}' đang có Hợp Đồng thuê còn hiệu lực!");
            }
            //chặn nếu phòng đang ở trạng thái đã thuê hoặc đặt cọc
            $restrictedStatuses = ['rented','deposited','expiring_soon','pending_renewal'];
            if(in_array($room->status, $restrictedStatuses)){
                throw new \Exception("Không thể xoá tầng này vì phòng '{$room->name}' đang trong trạng thái Đã thuê hoặc Đặt cọc!");
            }
        }

        // Xóa ảnh của tất cả phòng trong tầng
        foreach ($floor->rooms as $room) {
            $this->deleteRoomImages($room);
        }
        return $this->floorRepo->delete($floor);
    }

    // ========== ROOM (Phòng) ==========

    /**
     * Thống kê số phòng theo trạng thái
     */
    public function getStatusCounts(int $landlordId, ?int $boardingHouseId = null): array
    {
        if ($boardingHouseId) {
            $counts = $this->roomRepo->countByStatusForBoardingHouse($boardingHouseId);
        } else {
            $counts = $this->roomRepo->countByStatusForLandlord($landlordId);
        }

        $result = [];
        foreach (Room::STATUSES as $status) {
            $result[$status] = $counts[$status] ?? 0;
        }
        return $result;
    }

    /**
     * Tạo phòng mới (có upload ảnh)
     */
    public function createRoom(int $landlordId, array $data, array $imageFiles = [], ?int $boardingHouseId = null): ?Room
    {
        // Kiểm tra floor thuộc về landlord
        $floor = $this->floorRepo->findById($data['floor_id']);
        if (!$floor || $floor->property->landlord_id !== $landlordId)
            return null;

        $imagePaths = $this->uploadImages($imageFiles);

        if ($boardingHouseId) {
            $boardingHouse = \App\Models\BoardingHouse::where('id', $boardingHouseId)->where('user_id', $landlordId)->first();
        } else {
            $boardingHouse = \App\Models\BoardingHouse::where('user_id', $landlordId)->first();
        }

        // Kiểm tra trùng số phòng trong cùng tầng và cùng cơ sở
        $query = Room::where('floor_id', $data['floor_id'])
            ->where('room_number', $data['room_number']);
        if ($boardingHouse) {
            $query->where('boarding_house_id', $boardingHouse->id);
        }
        $exists = $query->exists();

        if ($exists)
            return null;

        $room = $this->roomRepo->create([
            'boarding_house_id' => $boardingHouse ? $boardingHouse->id : null,
            'property_id'     => $floor->property_id ?? null,
            'floor_id'        => $data['floor_id'],
            'room_number'     => $data['room_number'],
            'address'         => $data['address'] ?? null,
            'latitude'        => $data['latitude'] ?? null,
            'longitude'       => $data['longitude'] ?? null,
            'price'           => $data['price'],
            'area'            => $data['area'],
            'capacity'        => $data['capacity'] ?? 2,
            'status'          => $data['status'] ?? 'available',
            'amenities'       => $data['amenities'] ?? null,
            'images'          => $imagePaths ?: null,
        ]);

        if (isset($data['service_ids']) && is_array($data['service_ids'])) {
            $room->services()->sync($data['service_ids']);
            $serviceNames = $room->services()->pluck('name')->toArray();
            $room->update(['amenities' => implode(', ', $serviceNames)]);
        }

        return $room;
    }

    /**
     * Cập nhật phòng (có upload ảnh mới + xóa ảnh cũ)
     */
    public function updateRoom(int $landlordId, int $roomId, array $data, array $newImageFiles = [], array $removedImages = []): bool
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId)
            return false;

        //chặn người dùng chỉnh sửa nếu phòng đó đã có tin đăngg được hiển thị ở clien
        if ($room->roomPosts()->where('status', 'approved')->exists()) {
            throw new \Exception('Không thể chỉnh sửa thông tin phòng vì phòng này đã có tin được hiển thị');
        }

        if (isset($data['status']) && !in_array($data['status'], Room::STATUSES))
            return false;

        // Kiểm tra trùng số phòng trong cùng tầng và cùng cơ sở (bỏ qua chính nó)
        if (isset($data['room_number'])) {
            $query = Room::where('floor_id', $room->floor_id)
                ->where('room_number', $data['room_number'])
                ->where('id', '!=', $roomId);

            if ($room->boarding_house_id) {
                $query->where('boarding_house_id', $room->boarding_house_id);
            }

            $exists = $query->exists();
            if ($exists)
                return false;
        }

        // Xử lý ảnh: giữ ảnh cũ, xóa ảnh bị remove, thêm ảnh mới
        $currentImages = $room->images ?? [];
        // Xóa ảnh được đánh dấu remove
        foreach ($removedImages as $img) {
            Storage::disk('public')->delete($img);
            $currentImages = array_filter($currentImages, fn($i) => $i !== $img);
        }
        // Upload ảnh mới
        $newPaths = $this->uploadImages($newImageFiles);
        $allImages = array_values(array_merge($currentImages, $newPaths));

        $updateData = array_filter([
            'room_number' => $data['room_number'] ?? null,
            'address' => array_key_exists('address', $data) ? $data['address'] : null,
            'latitude' => array_key_exists('latitude', $data) ? $data['latitude'] : null,
            'longitude' => array_key_exists('longitude', $data) ? $data['longitude'] : null,
            'price' => $data['price'] ?? null,
            'area' => $data['area'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'status' => $data['status'] ?? null,
            'amenities' => $data['amenities'] ?? null,
        ], fn($v) => $v !== null);

        if (array_key_exists('maintenance_reason', $data)) {
            $updateData['maintenance_reason'] = ($data['status'] ?? $room->status) === 'maintenance' ? $data['maintenance_reason'] : null;
        } else if (isset($data['status']) && $data['status'] !== 'maintenance') {
            $updateData['maintenance_reason'] = null;
        }

        $updateData['images'] = $allImages ?: null;

        $updated = $this->roomRepo->update($room, $updateData);

        if ($updated && isset($data['service_ids']) && is_array($data['service_ids'])) {
            $room->services()->sync($data['service_ids']);
            $serviceNames = $room->services()->pluck('name')->toArray();
            $room->update(['amenities' => implode(', ', $serviceNames)]);
        }

        return $updated;
    }

    /**
     * Đổi trạng thái nhanh
     */
    public function changeStatus(int $landlordId, int $roomId, string $status, ?string $reason = null)
    {
        if (!in_array($status, Room::STATUSES))
            return false;
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId)
            return false;

        if (in_array($room->status, ['pending_renewal', 'deposited']) && $status === 'rented' && $room->current_people <= 0) {
            return 'empty_people';
        }

        $updateData = ['status' => $status];
        if (in_array($status, ['rented', 'deposited']) && $room->current_people <= 0) {
            $updateData['current_people'] = 1;
        }

        if ($status === 'maintenance') {
            $updateData['maintenance_reason'] = $reason;
        } else {
            $updateData['maintenance_reason'] = null;
        }

        return $this->roomRepo->update($room, $updateData);
    }

    /**
     * Thêm người vào phòng
     */
    public function addPerson(int $landlordId, int $roomId)
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId)
            return false;

        $allowedStatuses = ['deposited', 'rented', 'expiring_soon', 'pending_renewal'];
        if (!in_array($room->status, $allowedStatuses)) {
            return 'invalid_status';
        }

        if ($room->current_people >= $room->capacity) {
            return 'full';
        }

        return $this->roomRepo->update($room, ['current_people' => $room->current_people + 1]);
    }

    /**
     * Bớt người khỏi phòng
     */
    public function removePerson(int $landlordId, int $roomId)
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId)
            return false;

        if (!in_array($room->status, ['rented', 'deposited', 'pending_renewal', 'expiring_soon'])) {
            return 'invalid_status';
        }

        $currentPeople = max($room->current_people ?? 0, 1);
        if ($currentPeople <= 0) {
            return 'empty';
        }

        return $this->roomRepo->update($room, ['current_people' => max(0, $currentPeople - 1)]);
    }

    //Phần thêm người ở ghép
    public function addResident(int $landlordId, int $roomId, string $phone, string $startDate)
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->boardingHouse->user_id !== $landlordId) {
            throw new \Exception('Không tìm thấy phòng trọ hoặc bạn không có quyền.');
        }

        // Tìm User B theo SĐT
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            throw new \Exception('Không tìm thấy tài khoản người dùng có SĐT này trên hệ thống. Yêu cầu B đăng ký tài khoản trước!');
        }

        // Check CCCD của B
        $cccd = trim($user->cccd_number ?? '');
        if (empty($cccd) || strlen($cccd) !== 12 || !is_numeric($cccd)) {
            throw new \Exception('Người dùng "' . $user->name . '" chưa cập nhật đúng số CCCD 12 số. Hãy nhắc B cập nhật profile trước.');
        }

        // Check người dùng đã có ở trong phòng chưa
        $existing = RoomResident::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
        if ($existing) {
            throw new \Exception('Người dùng này hiện đã đang ở ghép trong phòng này rồi!');
        }

        // Check sức chứa
        if ($room->current_people >= $room->capacity) {
            throw new \Exception('Phòng đã đầy số lượng người tối đa, không thể thêm thành viên ở ghép.');
        }

        // Lưu bản ghi ở ghép
        RoomResident::create([
            'room_id' => $roomId,
            'user_id' => $user->id,
            'start_date' => $startDate,
            'status' => 'active',
        ]);
        // tăng số lượng người ở hiện tại của phòng
        $room->increment('current_people');
        $room->refresh();

        // 1. Chuyển trạng thái lịch hẹn của người dùng này sang 'joined_roommate' (Đã tham gia ở ghép)
        \App\Models\Appointment::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->update(['status' => 'joined_roommate']);

        // 2. Nếu số lượng người đã ĐẠT HOẶC VƯỢT quá sức chứa (current_people >= capacity) -> TỰ ĐỘNG ẨN TIN ĐĂNG
        if ($room->current_people >= $room->capacity) {
            // Đổi trạng thái tin đăng sang 'hidden' để ẩn bài đăng (Đóng tin đăng)
            \App\Models\RoomPost::where('room_id', $roomId)->update(['status' => 'hidden']);

            // Đóng tất cả các lịch hẹn còn đang chờ khác của phòng này
            \App\Models\Appointment::where('room_id', $roomId)
                ->whereIn('status', ['approved', 'viewed', 'waiting_contract', 'pending'])
                ->update(['status' => 'completed']);
        }

        // 3. Tự động chuyển các yêu cầu ở ghép đang chờ (pending) của phòng này sang 'approved'
        \App\Models\RoommateRequest::where('room_id', $roomId)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }

    // Xoá thành viên ở ghép khỏi phòng
    public function removeResident(int $landlordId, int $roomId, int $residentId)
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->boardingHouse->user_id !== $landlordId) {
            throw new \Exception('Không tìm thấy phòng trọ hoặc bạn không có quyền.');
        }

        $resident = RoomResident::where('room_id', $roomId)
            ->where('id', $residentId)
            ->where('status', 'active')
            ->first();

        if (!$resident) {
            throw new \Exception('Không tìm thấy thành viên ở ghép này trong phòng.');
        }

        $resident->update([
            'status' => 'inactive',
            'end_date' => now()->format('Y-m-d'),
        ]);
        // Giảm số lượng người ở thực tế
        $room->decrement('current_people');
        //gửi thông báo cho user bị xoá khỏi phòng
        $user = $resident->user;
        if ($user) {
            $roomNum = $room->room_number ?? '';
            $houseName = $room->boardingHouse->name ?? 'nhà trọ';
            $user->notify(new \App\Notifications\AdminNotification(
                'Thông báo cập nhật cư dân phòng trọ',
                "Bạn đã được chủ trọ cập nhật xoá khỏi danh sách cư dân ở ghép tại phòng {$roomNum} ({$houseName}).",
                route('tranguser')
            ));
        }
        return true;
    }

    /**
     * Xóa phòng
     */
    public function deleteRoom(int $landlordId, int $roomId): bool
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId)
            return false;
        //chặn xoá phòng nếu tin đó đã được hiển thị ở clien
        if ($room->roomPosts()->where('status', 'approved')->exists()) {
            throw new \Exception('Không thể xoá phòng vì phòng này đã có tin được hiển thị');
        }
        $this->deleteRoomImages($room);
        return $this->roomRepo->delete($room);
    }

    // ========== PRIVATE HELPERS ==========

    private function formatRoom($room): array
    {
        $currentPeople = (int) ($room->current_people ?? 0);

        //Đếm số hợp đồng đang hoạt động/ký cho phòng này
        $activeContractsCount = \App\Models\Contract::where('room_id', $room->id)
            ->whereIn('status', ['active', 'signed', 'pending', 'awiting_upload', 'termination_requested', 'expiring'])
            ->count();

        //số người thực tế là giá trị lớn nhất giữa số hợp đồng active và số cư dân active
        $activeResidentsCount = \App\Models\RoomResident::where('room_id', $room->id)
            ->where('status', 'active')
            ->count();
        //số người thực tế là giá trị lớn nhất giữa hợp đồng active và cư dân active
        $currentPeople = max($activeContractsCount, $activeResidentsCount);
        //tự động đồng bộ lại số người ở vào db
        if ((int) $room->current_people !== $currentPeople) {
            $room->update([
                'current_people' => $currentPeople
            ]);
        }
        return [
            'id' => $room->id,
            'name' => $room->room_number,
            'address' => $room->address,
            'latitude' => $room->latitude,
            'longitude' => $room->longitude,
            'status' => $room->status,
            'is_frozen' => $room->is_frozen,
            'maintenance_reason' => $room->maintenance_reason,
            'price' => (float) $room->price,
            'area' => (float) $room->area,
            'capacity' => $room->capacity,
            'current_people' => $currentPeople,
            'amenities' => $room->amenities,
            'images' => $room->images ?? [],
            'services' => $room->relationLoaded('services') ? $room->services->map(function($service) {
                $srv = $service->toArray();
                if ($service->pivot && !is_null($service->pivot->price)) {
                    $srv['price'] = (float)$service->pivot->price;
                }
                return $srv;
            })->toArray() : [],
            'has_approved_post' => $room->roomPosts()->where('status', 'approved')->exists(),
            'residents' => $room->residents()->with('user')->get()->map(function ($r) {
                return [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'name' => $r->user->name ?? 'Thành viên',
                    'phone' => $r->user->phone ?? '',
                    'cccd_number' => $r->user->cccd_number ?? '',
                    'start_date' => $r->start_date,
                ];
            })->toArray(),
        ];
    }

    private function uploadImages(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->store('rooms', 'r2_public');
        }
        return $paths;
    }

    private function deleteRoomImages($room): void
    {
        if (!empty($room->images)) {
            foreach ($room->images as $img) {
                Storage::disk('r2_public')->delete($img);
            }
        }
    }
}
