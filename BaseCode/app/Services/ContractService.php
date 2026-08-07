<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Contract;
use App\Models\ContractExtension;
use App\Models\Room;
use App\Models\User;
use App\Models\RoomPost;
use App\Models\RoomResident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RoommateRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractService
{
    // tạo hợp đồng mới (Luồng 4 bước: Direct Upload,Gate CCCD Check)\
    public function createContract(array $data, $file)
    {
        return DB::transaction(function () use ($data, $file) {
            $appointment = null;
            if (!empty($data['appointment_id'])) {
                // Trường hợp 1: Ký từ lịch hẹn
                $appointment = Appointment::with(['user', 'room'])->findOrFail($data['appointment_id']);
                $tenant = $appointment->user;
                $room = $appointment->room;
            } else {
                // Trường hợp 2: Ký trực tiếp cho người ở ghép (không qua lịch hẹn)
                $tenant = User::findOrFail($data['tenant_id']);
                $room = Room::findOrFail($data['room_id']);
            }
            if (!$tenant) {
                throw new \Exception('Không tìm thấy thông tin khách thuê.');
            }
            //1.Kiểm tra trạng thái lịch hẹn trước khi ký(nếu có lịch hẹn)
            if ($appointment) {
                if ($appointment->status === 'success_matched') {
                    throw new \Exception('Lịch hẹn này đã được dùng để tạo hợp đồng thành công trước đó.');
                }
                if (in_array($appointment->status, ['cancelled', 'expired', 'denied'])) {
                    throw new \Exception('Lịch hẹn này đã bị huỷ, từ chỗi hoặc hết hạn, không thể dùng để tạo hợp đồng.');
                }
            }
            //2. Check cccd kiểm tra định danh khách thuê có đúng 12 chứ số
            $cccd = trim($tenant->cccd_number ?? '');
            if (empty($cccd) || strlen($cccd) !== 12 || !is_numeric($cccd)) {
                throw new \Exception('Khách thuê"' . $tenant->name . '" chưa cập nhật đúng số CCCD 12 chữ số trên trang cá nhân của họ. Vui lòng nhắc khách cập nhật trước khi tạo hợp đồng!');
            }
            //3. Ràng buộc pháp lý: Mỗi khách thuê chỉ được có tối đa 1 hợp đồng có hiệu lực
            $existingContract = Contract::where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'])
                ->first();
            if ($existingContract) {
                throw new \Exception('Khách thuê này hiện đã có 1 hợp đồng thuê trọ đang có hiệu lực trong hệ thống.');
            }
            if (!$room) {
                throw new \Exception('Không tìm thấy thông tin phòng trọ.');
            }
            //check sức chứa của phòng
            $numberOfTenants = (int) ($data['number_of_tenants'] ?? 1);
            $availableCapacity = max(1, $room->capacity - $room->current_people);

            //nếu người đó đang ở ghép sẵn trong phòng, sẽ không bị tính là người mới chiếm thêm chỗ
            $isAlreadyResident = RoomResident::where('room_id', $room->id)
                ->where('user_id', $tenant->id)
                ->where('status', 'active')
                ->exists();

            if (!$isAlreadyResident && $numberOfTenants > $availableCapacity) {
                throw new \Exception("Số lượng người ở ({$numberOfTenants} người) vượt quá sức chứa còn lại của phòng (Còn trống {$availableCapacity} chỗ).");
            }
            //4. Upload file đính kèm trực tiếp & tự động chuyển ảnh sang pdf
            $filePath = null;
            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['jpg', 'png', 'jpeg'])) {
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $mimeType = $file->getMimeType();
                    $src = 'data:' . $mimeType . ';base64,' . $imageData;
                    //tạo html chứa ảnh để xuất sang PDF
                    $html = '<html><head><style>
                        body { margin: 0; padding: 0; text-align: center; background-color: #ffffff; }
                        img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
                    </style></head><body><img src="' . $src . '" /></body></html>';

                    //tạo file PDF sử dụng DomPDF
                    $pdf = Pdf::loadHTML($html);
                    //lưu file PDF
                    $fileName = 'contracts/documents/' . uniqid() . '.pdf';
                    Storage::disk('public')->put($fileName, $pdf->output());
                    $filePath = $fileName;
                } else {
                    //nếu là tệp pdf thì sẽ lưu trực tiếp
                    $filePath = $file->store('contracts/documents', 'public');
                }
            }
            if (!$filePath) {
                throw new \Exception('Không thể tải file hợp đồng lên. Vui lòng thử lại.');
                ;
            }
            //lưu DB & kích hoạt hợp đồng
            $contract = Contract::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'monthly_rent' => $room->price,
                'deposit_amount' => $data['deposit'],
                'number_of_tenants' => $numberOfTenants,
                'billing_cycle' => 1, // Mặc định chu kỳ đóng tiền hàng tháng
                'contract_file_path' => $filePath,
                'status' => 'signed',
                'signed_at' => now(),
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ]);
            //nếu người này đang ở ghép, chuyển sang inactive vì họ đã lên làm chủ hợp đồng chính thức
            if ($isAlreadyResident) {
                RoomResident::where('room_id', $room->id)
                    ->where('user_id', $tenant->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'inactive',
                        'end_date' => now()->format('Y-m-d')
                    ]);
            }
            //cập nhật trạng thái phòng trọ sang đã thuê(rented) và ẩn bào đăng
            //nếu là người ở ghép lên làm chủ, current_people không thay đổi, nếu không thì thêm người
            $newPeopleCount = $room->current_people;
            if (!$isAlreadyResident) {
                $newPeopleCount = min($room->capacity, max(1, $numberOfTenants));
            }
            $room->update([
                'status' => 'rented',
                'current_people' => $newPeopleCount
            ]);
            RoomPost::where('room_id', $room->id)->update(['status' => 'hidden']);
            //cập nhật lịch hẹn khớp thành công (nếu ký từ lịch hẹn)
            if ($appointment) {
                $appointment->update(['status' => 'success_matched']);
            }
            //phát event cập nhật trạng thái phòng
            event(new \App\Events\RoomStatusUpdated($room->id, 'rented'));
            //gửi thông báo tới khách thuê
            $tenant->notify(new \App\Notifications\ContractCreatedNotification($contract));
            return $contract;
        });
    }

    //Phần hợp đồng hết hạn
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
    //Phần thanh lý hợp đồng
    public function liquidateContract(Contract $contract, array $data)
    {
        return DB::transaction(function () use ($contract, $data) {
            $contract->update([
                'status' => 'terminated',
                'liquidated_at' => now(), // Đổi thành liquidated_at
                'deposit_handling' => $data['deposit_handling'], // Đổi thành deposit_handling
                'deposit_refund_amount' => $data['deposit_refund_amount'] ?? 0, // Đổi thành deposit_refund_amount
                'cancellation_reason' => $data['notes'] ?? null,
            ]);
            $room = $contract->room;
            if ($room) {
                $room->update([
                    'status' => 'available',
                    'current_people' => max(0, $room->current_people - $contract->number_of_tenants)
                ]);
                RoomPost::where('room_id', $room->id)->update(['status' => 'approved']);
                event(new \App\Events\RoomStatusUpdated($room->id, 'available'));
            }
            return $contract;
        });
    }

    //Phần gia hạn hợp đồng
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
                'new_end_date' => $contract->start_date,
                'ole_monthly_rent' => $contract->monthly_rent,
                'new_monthly_rent' => $contract->monthly_rent,
                'tenant_cccd_number' => $data['tenant_cccd'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $landlordId,
            ]);
            Contract::$allowImmutableUpdate = false;
            return $contract;
        });
    }
    //quét trạng thái hết hạn và hiệu lực hợp đồng (signed->active->exporing->expired)

    public function scanContractStatuses($landlordId = null)
    {
        $today = now()->format('Y-m-d');
        $exporingThreshold = now()->addDays(30)->format('Y-m-d');
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

            if ($endDateStr && $endDateStr <= $today && $contract->status !== 'expired') {
                //đến ngày kết thúc -> chuyển trạng thái sang hết hạn (Expired)
                $contract->update([
                    'status' => 'expired',
                    'cancellation_reason' => $contract->cancellation_reason ?: 'Hợp đồng tự đồnh chuyển sang Hết hạn do đã đến ngày kết thúc.'
                ]);
                $updatedCount++;
            } elseif ($endDateStr && $endDateStr <= $exporingThreshold && $endDateStr > $today && $contract->status !== 'expiring' && $contract->status !== 'expired') {
                //sắp đến ngày kết thúc (còn < 30 day) -> chuyển sang Expiring (sắp hết hạn)
                $contract->update([
                    'status' => 'expiring'
                ]);
                $updatedCount++;
            } elseif ($startDateStr && $startDateStr <= $today && $contract->status === 'signed') {
                // 3. Đã đến hoặc qua ngày bắt đầu -> Chuyển từ Signed sang Active (Đang hiệu lực)
                $contract->update([
                    'status' => 'active'
                ]);
                $updatedCount++;
            }
        }
        Contract::$allowImmutableUpdate = false;
        return $updatedCount;
    }

    //lấy danh sách yêu cầu ở ghép thuộc các phòng của chủ trọ
    public function getRoommateRequests($landlordId)
    {
        return RoommateRequest::whereHas('room.boardingHouse', function ($q) use ($landlordId) {
            $q->where('user_id', $landlordId);
        })
            ->with(['room.boardingHouse', 'tenant'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    //phê duyệt yêu cầu ở ghép
    public function approveRoommateRequest($requestId)
    {
        return DB::transaction(function () use ($requestId) {
            $request = RoommateRequest::with('room')->findOrFail($requestId);
            if ($request->status !== 'pending') {
                throw new \Exception('Yêu cầu này đã được xử lý từ trước.');
            }
            if ($request->type === 'stranger') {
                //luồng 1: Tìm người lạ -> Duyệt yêu cầu
                RoomPost::where('room_id', $request->room_id)->update(['status' => 'approved']);
                $request->status = 'approved';
                $request->save();
            } else if ($request->type === 'acquaintance') {
                //luồng 2: Giới thiệu người quen-> Thêm thành viên vào phòng 
                $user = User::where('phone', $request->new_resident_phone)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $request->new_resident_name,
                        'phone' => $request->new_resident_phone,
                        'email' => $request->new_resident_email ?: ($request->new_resident_phone . '@temp.com'),
                        'cccd_number' => $request->new_resident_cccd,
                        'password' => bcrypt('12345678'),
                        'role' => 'user',
                    ]);
                }
                RoomResident::create([
                    'room_id' => $request->room_id,
                    'user_id' => $user->id,
                    'start_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]);
                if ($request->room) {
                    $request->room->increment('current_people');
                }
                $request->status = 'approved';
                $request->save();
            }
            //gửi thông báo phản hồi lại cho khách thuê
            $tenant = $request->tenant;
            if ($tenant) {
                $typeLabel = $request->type === 'stranger' ? 'Tìm người ở ghép' : 'Giới thiệu người ở ghép';
                $tenant->notify(new \App\Notifications\AdminNotification(
                    'Yêu cầu ở ghép đã được duyệt',
                    "Yêu cầu '{$typeLabel}' cho phòng của bạn đã được chủ trọ phê duyệt thành công.",
                    route('quanlynoio')
                ));
            }
            return $request;
        });
    }

    //Phần từ chối yêu cầu ở ghép
    public function rejectRoommateRequest($requestId)
    {
        $request = RoommateRequest::findOrFail($requestId);
        if ($request->status !== 'pending') {
            throw new \Exception('yêu cầu này đã được xử lý từ trước.');
        }
        $request->status = 'rejected';
        $request->save();
        //gửi thông báo từ chối cho clien
        $tenant = $request->tenant;
        if ($tenant) {
            $typeLabel = $request->type === 'strangrt' ? 'Tìm người ở ghép (người lạ)' : 'Giới thiệu người ở ghép';
            $tenant = notify(new \App\Notifications\AdminNotification(
                'Yêu cầu ở ghép bị từ chối',
                "Yêu cầu '{$typeLabel}' cho phòng của bạn đã bị chủ nhà từ chối.",
                route('quanlynoio')
            ));
        }
        return $request;
    }
}

?>