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
        return $this->serviceRepo->update($service, $updateData);
    }

    public function deleteService(int $landlordId, int $serviceId)
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service->property->landlord_id !== $landlordId)
            return false;
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
