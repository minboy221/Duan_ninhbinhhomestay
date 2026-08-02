<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Floor;
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
            $rooms = $floor->rooms;
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
            ];
        })->values()->toArray();
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

        // Kiểm tra trùng tên tầng
        $exists = Floor::where('property_id', $propertyId)
            ->where('name', $data['name'])
            ->exists();
        if ($exists)
            return null;

        $maxOrder = $this->floorRepo->getMaxSortOrder($propertyId);

        $floor = $this->floorRepo->create([
            'property_id' => $propertyId,
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return [
            'id' => $floor->id,
            'name' => $floor->name,
            'address' => $floor->address,
            'latitude' => $floor->latitude,
            'longitude' => $floor->longitude,
            'rooms' => []
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
        if (!$floor || $floor->property->landlord_id !== $landlordId)
            return false;

        $restrictedStatuses = ['rented', 'deposited', 'expiring_soon', 'pending_renewal'];
        foreach ($floor->rooms as $room) {
            if (in_array($room->status, $restrictedStatuses)) {
                throw new \Exception('Tầng này có phòng đang trong trạng thái Đã thuê, Đã đặt cọc, Sắp hết hạn HĐ hoặc Chờ gia hạn. Không thể xóa!');
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

        if (!$boardingHouse) {
            $boardingHouse = \App\Models\BoardingHouse::create([
                'user_id' => $landlordId,
                'name' => ($floor && $floor->property) ? $floor->property->name : 'Nhà trọ chính',
                'district' => 'Chưa cập nhật',
                'address_detail' => ($floor && $floor->property) ? $floor->property->address : 'Chưa cập nhật',
                'status' => 'approved',
            ]);
        }

        $room = $this->roomRepo->create([
            'boarding_house_id' => $boardingHouse->id,
            'floor_id' => $data['floor_id'],
            'room_number' => $data['room_number'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'price' => $data['price'],
            'area' => $data['area'],
            'capacity' => $data['capacity'] ?? 2,
            'status' => $data['status'] ?? 'available',
            'amenities' => $data['amenities'] ?? null,
            'images' => $imagePaths ?: null,
        ]);

        if (isset($data['service_ids']) && is_array($data['service_ids'])) {
            $room->services()->sync($data['service_ids']);
        }

        return $room;
    }

    /**
     * Cập nhật phòng (có upload ảnh mới + xóa ảnh cũ)
     */
    public function updateRoom(int $landlordId, int $roomId, array $data, array $newImageFiles = [], array $removedImages = []): bool
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->boardingHouse->user_id !== $landlordId)
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
        if (!$room || $room->boardingHouse->user_id !== $landlordId)
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
        if (!$room || $room->boardingHouse->user_id !== $landlordId)
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
        if (!$room || $room->boardingHouse->user_id !== $landlordId)
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

    /**
     * Xóa phòng
     */
    public function deleteRoom(int $landlordId, int $roomId): bool
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->boardingHouse->user_id !== $landlordId)
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

        // Đếm số hợp đồng đang hoạt động/ký cho phòng này
        $activeContractsCount = \App\Models\Contract::where('room_id', $room->id)
            ->whereIn('status', ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'])
            ->count();

        // Nếu room có trạng thái 'rented' (Đã thuê) hoặc có HĐ active, tự động sinh ít nhất 1 người ở
        if ($room->status === 'rented' || $activeContractsCount > 0) {
            $currentPeople = max($currentPeople, $activeContractsCount, 1);

            // Cập nhật đồng bộ lại vào cơ sở dữ liệu nếu DB đang lưu = 0
            if (($room->current_people ?? 0) < $currentPeople) {
                $room->update(['current_people' => $currentPeople]);
            }
        }

        return [
            'id' => $room->id,
            'name' => $room->room_number,
            'address' => $room->address,
            'latitude' => $room->latitude,
            'longitude' => $room->longitude,
            'status' => $room->status,
            'maintenance_reason' => $room->maintenance_reason,
            'price' => (float) $room->price,
            'area' => (float) $room->area,
            'capacity' => $room->capacity,
            'current_people' => $currentPeople,
            'amenities' => $room->amenities,
            'images' => $room->images ?? [],
            'services' => $room->relationLoaded('services') ? $room->services->toArray() : [],
            'has_approved_post' => $room->roomPosts()->where('status', 'approved')->exists(),
        ];
    }

    private function uploadImages(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->store('rooms', 'public');
        }
        return $paths;
    }

    private function deleteRoomImages($room): void
    {
        if (!empty($room->images)) {
            foreach ($room->images as $img) {
                Storage::disk('public')->delete($img);
            }
        }
    }
}
