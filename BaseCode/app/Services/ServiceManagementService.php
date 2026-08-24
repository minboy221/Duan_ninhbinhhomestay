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
        return $this->getConfiguredServices($landlordId, $boardingHouseId);
    }

    public function getConfiguredServices(int $landlordId, ?int $boardingHouseId = null)
    {
        $propertyId = $this->getOrCreatePropertyId($landlordId);

        if ($boardingHouseId) {
            $configuredServices = \App\Models\Service::where('property_id', $propertyId)
                ->where(function ($q) use ($boardingHouseId) {
                    $q->where('boarding_house_id', $boardingHouseId)
                        ->orWhereNull('boarding_house_id');
                })->get();
        } else {
            $configuredServices = $this->serviceRepo->getByPropertyId($propertyId);
        }

        // Tự động khởi tạo Điện và Nước nếu chưa có dịch vụ nào
        if ($configuredServices->isEmpty()) {
            $elecAmenity = \App\Models\Amenity::where('name', 'like', '%điện%')->first();
            $waterAmenity = \App\Models\Amenity::where('name', 'like', '%nước%')->first();

            if ($elecAmenity) {
                $this->serviceRepo->create([
                    'property_id' => $propertyId,
                    'boarding_house_id' => $boardingHouseId,
                    'amenity_id' => $elecAmenity->id,
                    'name' => $elecAmenity->name,
                    'icon' => $elecAmenity->icon,
                    'price' => 3500,
                    'type' => 'per_kwh',
                    'is_active' => true,
                    'color' => 'rose',
                ]);
            }
            if ($waterAmenity) {
                $this->serviceRepo->create([
                    'property_id' => $propertyId,
                    'boarding_house_id' => $boardingHouseId,
                    'amenity_id' => $waterAmenity->id,
                    'name' => $waterAmenity->name,
                    'icon' => $waterAmenity->icon,
                    'price' => 20000,
                    'type' => 'per_m3',
                    'is_active' => true,
                    'color' => 'blue',
                ]);
            }

            if ($boardingHouseId) {
                $configuredServices = \App\Models\Service::where('property_id', $propertyId)
                    ->where(function ($q) use ($boardingHouseId) {
                        $q->where('boarding_house_id', $boardingHouseId)
                            ->orWhereNull('boarding_house_id');
                    })->get();
            } else {
                $configuredServices = $this->serviceRepo->getByPropertyId($propertyId);
            }
        }

        return $configuredServices->sort(function ($a, $b) {
            $pA = $a->is_active ? 2 : 1;
            $pB = $b->is_active ? 2 : 1;
            if ($pA !== $pB) {
                return $pB <=> $pA;
            }
            return strcmp($a->name ?? '', $b->name ?? '');
        })->values();
    }

    public function getAvailableAmenities(int $landlordId, ?int $boardingHouseId = null)
    {
        $configuredServices = $this->getConfiguredServices($landlordId, $boardingHouseId);
        $usedAmenityIds = $configuredServices->pluck('amenity_id')->filter()->toArray();

        return \App\Models\Amenity::where('is_active', true)
            ->whereNotIn('id', $usedAmenityIds)
            ->get();
    }

    public function createService(int $landlordId, array $data)
    {
        $propertyId = $this->getOrCreatePropertyId($landlordId);
        $data['property_id'] = $propertyId;

        $boardingHouseId = $data['boarding_house_id'] ?? session('selected_boarding_house_id');
        if ($boardingHouseId) {
            $data['boarding_house_id'] = $boardingHouseId;
        }

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
        if (!$service || $service->property->landlord_id !== $landlordId)
            return false;

        $updateData = array_intersect_key($data, array_flip(['price', 'type', 'description', 'is_active', 'color']));

        if (isset($data['price']) && (float) $data['price'] !== (float) $service->price) {
            $oldPrice = (float) $service->price;
            $newPrice = (float) $data['price'];
            $houseName = $service->boardingHouse->name ?? '';

            $isElectricityOrWater = in_array($service->type, ['per_kwh', 'per_m3']) ||
                stripos($service->name, 'điện') !== false ||
                stripos($service->name, 'nước') !== false;

            if (!$isElectricityOrWater && $service->price_updated_at) {
                $lastUpdated = \Carbon\Carbon::parse($service->price_updated_at);
                if ($lastUpdated->diffInDays(\Carbon\Carbon::now()) < 30) {
                    throw new \Exception("Bạn chỉ được thay đổi giá dịch vụ này tối đa 1 lần trong 30 ngày!");
                }
            }

            // Get all room IDs linked to this service from room_service pivot
            $roomIds = \DB::table('room_service')
                ->where('service_id', $service->id)
                ->pluck('room_id');

            foreach ($roomIds as $roomId) {
                // Check if room has an active (signed) contract
                $hasActiveContract = \DB::table('contracts')
                    ->where('room_id', $roomId)
                    ->where('status', 'signed')
                    ->exists();
                if ($hasActiveContract && !$isElectricityOrWater) {
                    $pivot = \DB::table('room_service')
                        ->where('room_id', $roomId)
                        ->where('service_id', $service->id)
                        ->first();
                    if ($pivot && is_null($pivot->price)) {
                        \DB::table('room_service')
                            ->where('room_id', $roomId)
                            ->where('service_id', $service->id)
                            ->update([
                                'price' => $service->price, // freeze previous price cho dịch vụ cố định
                                'updated_at' => \Carbon\Carbon::now()
                            ]);
                    }
                }
            }

            $updateData['price_updated_at'] = \Carbon\Carbon::now();

            // Tự động phát thông báo tới tất cả cư dân thuộc cơ sở trọ khi giá thay đổi
            try {
                $affectedContracts = \App\Models\Contract::where('status', 'signed')
                    ->when($service->boarding_house_id, function ($q) use ($service) {
                        $q->whereHas('room', function ($rq) use ($service) {
                            $rq->where('boarding_house_id', $service->boarding_house_id);
                        });
                    })
                    ->with(['tenant', 'room'])
                    ->get();

                $notifiedUsers = collect();
                foreach ($affectedContracts as $contract) {
                    if ($contract->tenant && !$notifiedUsers->contains($contract->tenant->id)) {
                        $notifiedUsers->push($contract->tenant->id);
                        $contract->tenant->notify(new \App\Notifications\ServicePriceUpdatedNotification($service, $oldPrice, $newPrice, $houseName));
                    }

                    $residents = \App\Models\RoomResident::where('room_id', $contract->room_id)
                        ->where('status', 'active')
                        ->with('user')
                        ->get();
                    foreach ($residents as $res) {
                        if ($res->user && !$notifiedUsers->contains($res->user->id)) {
                            $notifiedUsers->push($res->user->id);
                            $res->user->notify(new \App\Notifications\ServicePriceUpdatedNotification($service, $oldPrice, $newPrice, $houseName));
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Service price notification error: ' . $e->getMessage());
            }
        }

        return $this->serviceRepo->update($service, $updateData);
    }

    public function deleteService(int $landlordId, int $serviceId)
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service->property->landlord_id !== $landlordId)
            return false;

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
