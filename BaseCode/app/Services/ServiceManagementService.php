<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use App\Repositories\PropertyRepository;

class ServiceManagementService
{
    protected ServiceRepository $serviceRepo;
    protected PropertyRepository $propertyRepo;

    public function __construct(
        ServiceRepository $serviceRepo,
        PropertyRepository $propertyRepo
    ) {
        $this->serviceRepo = $serviceRepo;
        $this->propertyRepo = $propertyRepo;
    }

    public function getOrCreatePropertyId(int $landlordId): int
    {
        $properties = $this->propertyRepo->getByLandlordId($landlordId);
        if ($properties->isNotEmpty()) {
            return $properties->first()->id;
        }
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

    public function getServices(int $landlordId, ?int $boardingHouseId = null)
    {
        if ($boardingHouseId) {
            return $this->serviceRepo->getByBoardingHouseId($boardingHouseId);
        }

        $propertyId = $this->getOrCreatePropertyId($landlordId);
        $configuredServices = $this->serviceRepo->getByPropertyId($propertyId);
        
        $activeAmenities = \App\Models\Amenity::where('is_active', true)->get();
        $merged = collect();
        
        foreach ($activeAmenities as $amenity) {
            $service = $configuredServices->firstWhere('amenity_id', $amenity->id);
            if ($service) {
                if ($service->name !== $amenity->name || $service->icon !== $amenity->icon) {
                    $service->update([
                        'name' => $amenity->name,
                        'icon' => $amenity->icon
                    ]);
                }
                $merged->push($service);
            } else {
                $merged->push(new \App\Models\Service([
                    'property_id' => $propertyId,
                    'amenity_id'  => $amenity->id,
                    'name'        => $amenity->name,
                    'icon'        => $amenity->icon,
                    'price'       => 0,
                    'type'        => 'fixed',
                    'is_active'   => false,
                    'description' => '',
                    'color'       => 'emerald'
                ]));
            }
        }
        
        foreach ($configuredServices as $service) {
            if (is_null($service->amenity_id)) {
                $merged->push($service);
            }
        }
        
        return $merged;
    }

    public function createService(int $landlordId, array $data)
    {
        $propertyId = $this->getOrCreatePropertyId($landlordId);
        $data['property_id'] = $propertyId;
        
        if (isset($data['amenity_id'])) {
            $amenity = \App\Models\Amenity::find($data['amenity_id']);
            if ($amenity) {
                $data['name'] = $amenity->name;
                $data['icon'] = $amenity->icon;
            }
        }
        
        return $this->serviceRepo->create($data);
    }

    public function updateService(int $landlordId, int $serviceId, array $data)
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service->property->landlord_id !== $landlordId) return false;
        
        $updateData = array_intersect_key($data, array_flip(['price', 'type', 'description', 'is_active', 'color']));
        
        if (isset($data['price']) && (float)$data['price'] !== (float)$service->price) {
            $isElectricityOrWater = in_array($service->type, ['per_kwh', 'per_m3']) || 
                                    stripos($service->name, 'điện') !== false || 
                                    stripos($service->name, 'nước') !== false;
            
            if (!$isElectricityOrWater && $service->price_updated_at) {
                $lastUpdated = \Carbon\Carbon::parse($service->price_updated_at);
                if ($lastUpdated->diffInDays(\Carbon\Carbon::now()) < 30) {
                    throw new \Exception("Bạn chỉ được thay đổi giá dịch vụ này tối đa 1 lần trong 30 ngày!");
                }
            }
            
            // Get all rooms linked to this service
            $rooms = $service->rooms()->get();
            foreach ($rooms as $room) {
                // Check if room has an active (signed) contract
                $hasActiveContract = \DB::table('contracts')
                    ->where('room_id', $room->id)
                    ->where('status', 'signed')
                    ->exists();
                if ($hasActiveContract) {
                    // Freeze the previous price of this service for this room in room_service pivot if not already frozen
                    $pivot = \DB::table('room_service')
                        ->where('room_id', $room->id)
                        ->where('service_id', $service->id)
                        ->first();
                    if ($pivot && is_null($pivot->price)) {
                        \DB::table('room_service')
                            ->where('room_id', $room->id)
                            ->where('service_id', $service->id)
                            ->update([
                                'price' => $service->price, // freeze previous price
                                'updated_at' => \Carbon\Carbon::now()
                            ]);
                    }
                }
            }
            
            $updateData['price_updated_at'] = \Carbon\Carbon::now();
        }
        
        return $this->serviceRepo->update($service, $updateData);
    }

    public function deleteService(int $landlordId, int $serviceId)
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service->property->landlord_id !== $landlordId) return false;
        
        if ($service->is_active) {
            throw new \Exception("Không thể xóa tiện ích đang hoạt động! Vui lòng khóa tiện ích trước.");
        }
        
        $inUse = \DB::table('room_service')->where('service_id', $serviceId)->exists();
        if ($inUse) {
            throw new \Exception("Tiện ích này đang được sử dụng ở các phòng trọ của bạn!");
        }
        
        return $this->serviceRepo->delete($service);
    }

    public function changeStatus(int $landlordId, int $serviceId, bool $isActive)
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service->property->landlord_id !== $landlordId)
            return false;
        return $this->serviceRepo->update($service, ['is_active' => $isActive]);
    }
}
