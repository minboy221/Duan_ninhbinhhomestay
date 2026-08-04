<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Contract;
use App\Models\ContractExtension;
use App\Models\Room;
use App\Models\User;
use App\Models\RoomPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractService
{
    // tạo hợp đồng mới (Luồng 4 bước: Direct Upload,Gate CCCD Check)

    public function createContract(array $data, $file)
    {
        return DB::transaction(function () use ($data, $file) {
            $appointment = Appointment::with(['user', 'room'])->findOrFail($data['appointment_id']);
            $tenant = $appointment->user;
            if (!$tenant) {
                throw new \Exception('Không tìm thấy thông tin khách thuê.');
            }
            //phần kiểm tra trạng thái lịch hẹn trước khí ký
            if ($appointment->status === 'success_matched') {
                throw new \Exception('Lịch hẹn này đã được dùng để tạo hợp đồng thành công trước đó.');
            }
            if (in_array($appointment->status, ['cancelled', 'expired', 'denied'])) {
                throw new \Exception('Lịch hẹn này đã bị huỷ, từ chối, hoặc hết hạn, không thể dùng để tạo hợp đồng.');
            }
            $room = $appointment->room;
            if (!$room) {
                throw new \Exception('Không tìm thấy thông tin phòng trọ.');
            }// Kiểm tra sức chứa của phòng
            $numberOfTenants = (int) ($data['number_of_tenants'] ?? 1);
            $availableCapacity = max(1, $room->capacity - $room->current_people);
            if ($numberOfTenants > $availableCapacity) {
                throw new \Exception("Số lượng người ở ({$numberOfTenants} người) vượt quá sức chứa còn lại của phòng (Còn trống {$availableCapacity} chỗ).");
            }
            // 3. UPLOAD FILE ĐÍNH KÈM TRỰC TIẾP
            $filePath = null;
            if ($file) {
                $filePath = $file->store('contracts/documents', 'public');
            }
            if (!$filePath) {
                throw new \Exception('Không thể tải file hợp đồng lên. Vui lòng thử lại.');
            }
            // 4. LƯU DATABASE & KÍCH HOẠT HỢP ĐỒNG (SIGNED)
            $contract = Contract::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'monthly_rent' => $room->price,
                'deposit_amount' => $data['deposit'],
                'number_of_tenants' => $numberOfTenants,
                'billing_cycle' => (int) ($data['billing_cycle'] ?? 1),
                'contract_file_path' => $filePath,
                'status' => 'signed',
                'signed_at' => now(),
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ]);
            // Cập nhật trạng thái phòng trọ sang ĐÃ THUÊ (RENTED) và ẩn bài đăng
            $room->update([
                'status' => 'rented',
                'current_people' => min($room->capacity, max(1, $room->current_people + $numberOfTenants))
            ]);
            RoomPost::where('room_id', $room->id)->update(['status' => 'hidden']);

            // Cập nhật lịch hẹn khớp thành công
            $appointment->update(['status' => 'success_matched']);
            // Phát event cập nhật trạng thái phòng
            event(new \App\Events\RoomStatusUpdated($room->id, 'rented'));

            // Gửi thông báo đến khách thuê
            $tenant->notify(new \App\Notifications\ContractCreatedNotification($contract));
            return $contract;
        });
    }
    /**
     * Đánh dấu hợp đồng hết hạn
     */
    public function markAsExpired(Contract $contract, ?string $reason)
    {
        Contract::$allowImmutableUpdate = true;
        $contract->update([
            'status' => 'expired',
            'cancellation_reason' => $reason ?: 'Hợp đồng báo hết hạn.'
        ]);
        Contract::$allowImmutableUpdate = false;
        return $contract;
    }
    /**
     * Thanh lý hợp đồng
     */
    public function liquidateContract(Contract $contract, array $data)
    {
        return DB::transaction(function () use ($contract, $data) {
            $contract->update([
                'status' => 'terminated',
                'liquidated_at' => now(),
                'deposit_handling' => $data['deposit_handling'],
                'deposit_refund_amount' => $data['deposit_refund_amount'] ?? 0,
                'cancellation_reason' => $data['notes'] ?? null,
            ]);
            $room = $contract->room;
            if ($room) {
                $room->update([
                    'status' => 'available',
                    'current_people' => max(0, $room->current_people - $contract->number_of_tenants)
                ]);
                RoomPost::where('room_id', $room->id)->update(['status' => 'active']);
                event(new \App\Events\RoomStatusUpdated($room->id, 'available'));
            }
            return $contract;
        });
    }
    /**
     * Gia hạn hợp đồng
     */
    public function extendContract(Contract $contract, array $data, $landlordId)
    {
        return DB::transaction(function () use ($contract, $data, $landlordId) {
            $tenant = $contract->tenant;
            if ($tenant) {
                $tenant->update(['cccd_number' => $data['tenant_cccd']]);
            }
            ContractExtension::create([
                'contract_id' => $contract->id,
                'old_end_date' => $contract->end_date,
                'new_end_date' => $data['new_end_date'],
                'old_monthly_rent' => $contract->monthly_rent,
                'new_monthly_rent' => $contract->monthly_rent,
                'tenant_cccd_number' => $data['tenant_cccd'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $landlordId,
            ]);
            Contract::$allowImmutableUpdate = true;
            $contract->update([
                'end_date' => $data['new_end_date'],
                'status' => 'signed',
            ]);
            Contract::$allowImmutableUpdate = false;
            return $contract;
        });
    }
    /**
     * Quét trạng thái hết hạn hợp đồng
     */
    public function scanContractStatuses($landlordId = null)
    {
        $today = now()->format('Y-m-d');
        $expiringThreshold = now()->addDays(30)->format('Y-m-d');
        $query = Contract::whereIn('status', ['active', 'signed', 'expiring']);
        if ($landlordId) {
            $query->whereHas('room.boardingHouse', function ($q) use ($landlordId) {
                $q->where('user_id', $landlordId);
            });
        }
        $contracts = $query->get();
        $updatedCount = 0;
        Contract::$allowImmutableUpdate = true;
        foreach ($contracts as $contract) {
            $startDateStr = $contract->start_date ? (is_string($contract->start_date) ? substr($contract->start_date, 0, 10) : $contract->start_date->format('Y-m-d')) : null;
            $endDateStr = $contract->end_date ? (is_string($contract->end_date) ? substr($contract->end_date, 0, 10) : $contract->end_date->format('Y-m-d')) : null;
            if($endDateStr && $endDateStr <= $today && $contract->status !== 'expired'){
                // đến ngày kết thúc -> chuyển trạng thái sang Expired
                $contract -> update([
                    'status' => 'expired',
                    'cancellation_reason' => $contract->cancellation_reason ?: 'Hợp đồng tự động chuyển sang Hết hạn do đã đến ngày kết thúc.'
                ]);
                $updatedCount++;
            }elseif($endDateStr && $endDateStr <= $expiringThreshold && $endDateStr >$today && $contract->status !== 'expiring' && $contract -> status !== 'expired'){
                //xắp xếp theo ngày kết thúc (còn < 30 ngày) -> chuyển sang sắp hết hạn
                $contract->update([
                    'status' => 'expiring'
                ]);
                $updatedCount++;
            }elseif($startDateStr && $startDateStr <= $today && $contract->status === 'signed'){
                //đã đến ngày bắt đầu -> chuyển từ signed sang acvive
                $contract->update([
                    'status' => 'active'
                ]);
            }
        }
        Contract::$allowImmutableUpdate = false;
        return $updatedCount;
    }
}

?>