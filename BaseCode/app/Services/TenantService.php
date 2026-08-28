<?php

namespace App\Services;

use App\Repositories\Interfaces\TenantRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TenantService
{
    protected TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $tenantRepository)
    {
        $this->tenantRepository = $tenantRepository;
    }

    public function getFormattedTenants(int $landlordId, ?int $boardingHouseId = null)
    {
        $data = $this->tenantRepository->getTenantsByLandlord($landlordId, $boardingHouseId);
        $residents = $data['residents'] ?? collect();
        $contracts = $data['contracts'] ?? collect();
        $rawList = collect();
        // 1. Xử lý người ở ghép (RoomResidents)
        foreach ($residents as $r) {
            $user = $r->user;
            $room = $r->room;
            $floor = $room ? $room->floor : null;
            $verification = $user ? $user->verification : null;
            //check số cccd chính xác theo db
            $cccdNumber = $verification->id_card_number ?? $user->cccd_number ?? $user->cccd ?? 'Chưa cập nhật';
            // Xác định trạng thái (Đang ở / Sắp rời đi / Khách cũ)
            $status = 'active';
            if (in_array($r->status, ['inactive', 'left']) || ($r->end_date && Carbon::parse($r->end_date)->isPast())) {
                $status = 'past'; // Khách cũ đã ở trước đó
            } else if ($r->status === 'leaving' || ($r->end_date && Carbon::parse($r->end_date)->diffInDays(now()) <= 30)) {
                $status = 'leaving';
            }
            $rawList->push([
                'user_id' => $r->user_id,
                'name' => $user ? $user->name : 'Cư dân',
                'phone' => $user ? $user->phone : 'N/A',
                'cccd' => $cccdNumber,
                'room_id' => $r->room_id,
                'room' => $room ? $room->room_number : 'N/A',
                'floor_id' => $floor ? $floor->id : null,
                'floor' => $floor ? $floor->name : 'N/A',
                'moveIn' => $r->start_date
                    ? Carbon::parse($r->start_date)->format('Y-m-d')
                    : ($r->created_at ? $r->created_at->format('Y-m-d') : 'N/A'),
                'role' => 'Ở ghép',
                'status' => $status,
                'contract_pdf' => null,
                'avatar' => $user && $user->name ? mb_strtoupper(mb_substr($user->name, 0, 1)) : 'C',
            ]);
        }
        // 2. Xử lý Khách thuê chính (Contracts)
        foreach ($contracts as $c) {
            $tenant = $c->tenant;
            $room = $c->room;
            $floor = $room ? $room->floor : null;
            $verification = $tenant ? $tenant->verification : null;
            $cccdNumber = $verification->id_card_number ?? $tenant->cccd_number ?? $tenant->cccd ?? 'Chưa cập nhật';
            $status = 'active';
            if (in_array($c->status, ['expired', 'cancelled', 'terminated', 'liquidated']) || ($c->end_date && Carbon::parse($c->end_date)->isPast())) {
                $status = 'past';
            } else if (in_array($c->status, ['expiring', 'pending_renewal']) || ($c->end_date && Carbon::parse($c->end_date)->diffInDays(now()) <= 30)) {
                $status = 'leaving';
            }
            //đường dẫn pdf hợp đồng
            $contractPdfUrl = null;
            if ($c->contract_file_path) {
                $contractPdfUrl = Storage::url($c->contract_file_path);
            } else if ($c->signed_contract_image) {
                $contractPdfUrl = Storage::url($c->signed_contract_image);
            }
            $rawList->push([
                'user_id' => $c->tenant_id,
                'name' => $tenant ? $tenant->name : 'Khách thuê chính',
                'phone' => $tenant ? $tenant->phone : 'N/A',
                'cccd' => $cccdNumber,
                'room_id' => $c->room_id,
                'room' => $room ? $room->room_number : 'N/A',
                'floor_id' => $floor ? $floor->id : null,
                'floor' => $floor ? $floor->name : 'N/A',
                'moveIn' => $c->start_date
                    ? Carbon::parse($c->start_date)->format('Y-m-d')
                    : ($c->created_at ? $c->created_at->format('Y-m-d') : 'N/A'),
                'role' => 'Chủ hợp đồng',
                'status' => $status,
                'contract_id' => $c->id,
                'contract_pdf' => $contractPdfUrl,
                'avatar' => $tenant && $tenant->name ? mb_strtoupper(mb_substr($tenant->name, 0, 1)) : 'K',
            ]);
        }
        //lọc trùng lặp
        // Ưu tiên giữ lại bản ghi có trạng thái Active > Leaving > Past
        $statusPriority = ['active' => 1, 'leaving' => 2, 'past' => 3];
        $uniqueList = $rawList->groupBy(function ($item) {
            return $item['user_id'] . '_' . $item['room_id'];
        })->map(function ($group) use ($statusPriority) {
            // Sắp xếp ưu tiên trạng thái Active lên đầu
            return $group->sortBy(function ($item) use ($statusPriority) {
                return $statusPriority[$item['status']] ?? 99;
            })->first();
        })->values();
        return $uniqueList;
    }
}
