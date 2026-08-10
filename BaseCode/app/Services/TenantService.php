<?php

namespace App\Services;

use App\Repositories\Interfaces\TenantRepositoryInterface;
use Carbon\Carbon;

class TenantService
{
    protected TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $tenantRepository)
    {
        $this->tenantRepository = $tenantRepository;
    }

    public function getFormattedTenants(int $landlordId, ?int $boardingHouseId = null)
    {
        $roomResidents = $this->tenantRepository->getTenantsByLandlord($landlordId, $boardingHouseId);

        return $roomResidents->map(function ($resident) {
            $user = $resident->user;
            $room = $resident->room;
            $floor = $room ? $room->floor : null;
            $verification = $user ? $user->verification : null;

            // Status mapping
            // In frontend, 'active' and 'leaving' are used.
            $frontendStatus = 'active';
            if ($resident->status === 'leaving' || ($resident->end_date && Carbon::parse($resident->end_date)->diffInDays(now()) <= 30 && Carbon::parse($resident->end_date)->isFuture())) {
                 $frontendStatus = 'leaving';
            }
            if ($resident->status === 'left') {
                return null;
            }

            return [
                'id' => $resident->id,
                'name' => $user ? $user->name : 'N/A',
                'phone' => $user ? $user->phone : 'N/A',
                'cccd' => $verification ? $verification->id_card_number : 'Chưa cập nhật',
                'room' => $room ? $room->room_number : 'N/A',
                'floor' => $floor ? $floor->name : 'N/A',
                'moveIn' => $resident->start_date,
                'people' => $room ? $room->current_people : 1,
                'status' => $frontendStatus,
                'avatar' => $user && $user->name ? mb_strtoupper(mb_substr($user->name, 0, 1)) : 'U',
            ];
        })->filter()->values();
    }
}
