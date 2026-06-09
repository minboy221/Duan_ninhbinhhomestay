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
        $this->roomRepo     = $roomRepo;
        $this->propertyRepo = $propertyRepo;
        $this->floorRepo    = $floorRepo;
    }

    // ========== FLOOR (Tầng) ==========

    /**
     * Lấy danh sách tầng kèm phòng cho trang quản lý
     */
    public function getFloorsWithRooms(int $landlordId): array
    {
        $properties = $this->propertyRepo->getByLandlordId($landlordId);
        if ($properties->isEmpty()) return [];

        // Lấy property đầu tiên (hoặc có thể mở rộng cho nhiều property)
        $property = $properties->first();
        $floors   = $this->floorRepo->getByPropertyId($property->id);

        return $floors->map(function ($floor) {
            return [
                'id'    => $floor->id,
                'name'  => $floor->name,
                'rooms' => $floor->rooms->map(fn($r) => $this->formatRoom($r))->values()->toArray(),
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
            'name'        => 'Nhà trọ chính',
            'address'     => 'Chưa cập nhật',
            'city'        => 'Ninh Bình',
            'type'        => 'motel_room',
            'is_active'   => true,
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
        if ($exists) return null;

        $maxOrder   = $this->floorRepo->getMaxSortOrder($propertyId);

        $floor = $this->floorRepo->create([
            'property_id' => $propertyId,
            'name'        => $data['name'],
            'sort_order'  => $maxOrder + 1,
        ]);

        return ['id' => $floor->id, 'name' => $floor->name, 'rooms' => []];
    }

    /**
     * Sửa tên tầng
     */
    public function updateFloor(int $landlordId, int $floorId, array $data): bool
    {
        $floor = $this->floorRepo->findById($floorId);
        if (!$floor || $floor->property->landlord_id !== $landlordId) return false;

        // Kiểm tra trùng tên tầng
        if (isset($data['name'])) {
            $exists = Floor::where('property_id', $floor->property_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $floorId)
                ->exists();
            if ($exists) return false;
        }

        return $this->floorRepo->update($floor, ['name' => $data['name']]);
    }

    /**
     * Xóa tầng (kèm toàn bộ phòng bên trong)
     */
    public function deleteFloor(int $landlordId, int $floorId): bool
    {
        $floor = $this->floorRepo->findById($floorId);
        if (!$floor || $floor->property->landlord_id !== $landlordId) return false;

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
    public function getStatusCounts(int $landlordId): array
    {
        $counts = $this->roomRepo->countByStatusForLandlord($landlordId);
        $result = [];
        foreach (Room::STATUSES as $status) {
            $result[$status] = $counts[$status] ?? 0;
        }
        return $result;
    }

    /**
     * Tạo phòng mới (có upload ảnh)
     */
    public function createRoom(int $landlordId, array $data, array $imageFiles = []): ?Room
    {
        // Kiểm tra floor thuộc về landlord
        $floor = $this->floorRepo->findById($data['floor_id']);
        if (!$floor || $floor->property->landlord_id !== $landlordId) return null;

        $imagePaths = $this->uploadImages($imageFiles);

        // Kiểm tra trùng số phòng trong cùng tầng
        $exists = Room::where('floor_id', $data['floor_id'])
            ->where('room_number', $data['room_number'])
            ->exists();
        if ($exists) return null;

        return $this->roomRepo->create([
            'property_id' => $floor->property_id,
            'floor_id'    => $data['floor_id'],
            'room_number' => $data['room_number'],
            'address'     => $data['address'] ?? null,
            'price'       => $data['price'],
            'area'        => $data['area'],
            'capacity'    => $data['capacity'] ?? 2,
            'status'      => $data['status'] ?? 'available',
            'amenities'   => $data['amenities'] ?? null,
            'images'      => $imagePaths ?: null,
        ]);
    }

    /**
     * Cập nhật phòng (có upload ảnh mới + xóa ảnh cũ)
     */
    public function updateRoom(int $landlordId, int $roomId, array $data, array $newImageFiles = [], array $removedImages = []): bool
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId) return false;

        if (isset($data['status']) && !in_array($data['status'], Room::STATUSES)) return false;

        // Kiểm tra trùng số phòng trong cùng tầng (bỏ qua chính nó)
        if (isset($data['room_number'])) {
            $exists = Room::where('floor_id', $room->floor_id)
                ->where('room_number', $data['room_number'])
                ->where('id', '!=', $roomId)
                ->exists();
            if ($exists) return false;
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
            'address'     => array_key_exists('address', $data) ? $data['address'] : null,
            'price'       => $data['price'] ?? null,
            'area'        => $data['area'] ?? null,
            'capacity'    => $data['capacity'] ?? null,
            'status'      => $data['status'] ?? null,
            'amenities'   => $data['amenities'] ?? null,
        ], fn($v) => $v !== null);

        if (array_key_exists('maintenance_reason', $data)) {
            $updateData['maintenance_reason'] = ($data['status'] ?? $room->status) === 'maintenance' ? $data['maintenance_reason'] : null;
        } else if (isset($data['status']) && $data['status'] !== 'maintenance') {
            $updateData['maintenance_reason'] = null;
        }

        $updateData['images'] = $allImages ?: null;

        return $this->roomRepo->update($room, $updateData);
    }

    /**
     * Đổi trạng thái nhanh
     */
    public function changeStatus(int $landlordId, int $roomId, string $status, ?string $reason = null): bool
    {
        if (!in_array($status, Room::STATUSES)) return false;
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId) return false;
        
        $updateData = ['status' => $status];
        if ($status === 'maintenance') {
            $updateData['maintenance_reason'] = $reason;
        } else {
            $updateData['maintenance_reason'] = null;
        }
        
        return $this->roomRepo->update($room, $updateData);
    }

    /**
     * Xóa phòng
     */
    public function deleteRoom(int $landlordId, int $roomId): bool
    {
        $room = $this->roomRepo->findById($roomId);
        if (!$room || $room->property->landlord_id !== $landlordId) return false;
        $this->deleteRoomImages($room);
        return $this->roomRepo->delete($room);
    }

    // ========== PRIVATE HELPERS ==========

    private function formatRoom($room): array
    {
        return [
            'id'        => $room->id,
            'name'      => $room->room_number,
            'address'   => $room->address,
            'status'    => $room->status,
            'maintenance_reason' => $room->maintenance_reason,
            'price'     => (float) $room->price,
            'area'      => (float) $room->area,
            'capacity'  => $room->capacity,
            'amenities' => $room->amenities,
            'images'    => $room->images ?? [],
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
