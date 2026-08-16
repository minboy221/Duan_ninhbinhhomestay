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
        return $this->serviceRepo->getByPropertyId($propertyId);
    }

    public function createService(int $landlordId, array $data)
    {
        $propertyId = $this->getOrCreatePropertyId($landlordId);
        $data['property_id'] = $propertyId;
        return $this->serviceRepo->create($data);
    }

    public function updateService(int $landlordId, int $serviceId, array $data)
    {
        $service = $this->serviceRepo->findById($serviceId);
        if (!$service || $service->property->landlord_id !== $landlordId)
            return false;
        return $this->serviceRepo->update($service, $data);
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
