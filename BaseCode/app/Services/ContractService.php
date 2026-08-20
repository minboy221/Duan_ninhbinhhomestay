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
use PHPUnit\Framework\TestStatus\Notice;

class ContractService
{
    // tạo hợp đồng mới (Luồng 4 bước: Direct Upload,Gate CCCD Check)\
    public function createContract(array $data, $files = null)
    {
        return DB::transaction(function () use ($data, $files) {
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

            // Trường hợp Thuê hộ (Người ở thực tế khác với người xem phòng)
            if (!empty($data['is_for_other']) && !empty($data['actual_tenant_phone']) && !empty($data['actual_tenant_cccd'])) {
                $actualPhone = trim($data['actual_tenant_phone']);
                $actualCccd = trim($data['actual_tenant_cccd']);
                $actualName = trim($data['actual_tenant_name'] ?? 'Cư dân thuê trọ');
                $actualEmail = !empty($data['actual_tenant_email']) ? trim($data['actual_tenant_email']) : null;

                // Tìm xem người con/người ở thực tế đã có tài khoản trên hệ thống chưa
                $actualTenant = User::where('phone', $actualPhone)
                    ->orWhere('cccd_number', $actualCccd)
                    ->when($actualEmail, function ($q) use ($actualEmail) {
                        $q->orWhere('email', $actualEmail);
                    })
                    ->first();

                $plainPassword = '12345678';

                // Nếu chưa có tài khoản -> Tự động khởi tạo tài khoản mới
                if (!$actualTenant) {
                    $actualTenant = User::create([
                        'name' => $actualName,
                        'phone' => $actualPhone,
                        'email' => $actualEmail,
                        'cccd_number' => $actualCccd,
                        'role' => 'tenant',
                        'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
                    ]);

                    // Gửi Email thông tin đăng nhập nếu có địa chỉ Email
                    if (!empty($actualTenant->email)) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($actualTenant->email)
                                ->send(new \App\Mail\NewTenantAccountMail(
                                    tenantName: $actualName,
                                    phone: $actualPhone,
                                    email: $actualTenant->email,
                                    password: $plainPassword,
                                    roomName: $room->room_number ?? '',
                                    boardingHouseName: $room->boardingHouse->name ?? ''
                                ));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Lỗi gửi email tài khoản thuê hộ: ' . $e->getMessage());
                        }
                    }
                } else {
                    // Nếu tài khoản đã tồn tại nhưng chưa có CCCD hoặc Email -> Cập nhật bổ sung
                    $updateData = [];
                    if (empty($actualTenant->cccd_number)) {
                        $updateData['cccd_number'] = $actualCccd;
                    }
                    if (empty($actualTenant->email) && $actualEmail) {
                        $updateData['email'] = $actualEmail;
                    }
                    if (!empty($updateData)) {
                        $actualTenant->update($updateData);
                    }
                }
                // Chuyển đối tượng ký Hợp đồng sang cho Cư dân ở thực tế!
                $tenant = $actualTenant;
            }

            // Bổ sung: Nếu chủ trọ gửi kèm số CCCD nhập từ Form cho luồng bình thường
            if (empty($data['is_for_other']) && !empty($data['tenant_cccd']) && strlen(trim($data['tenant_cccd'])) === 12 && is_numeric(trim($data['tenant_cccd']))) {
                $inputCccd = trim($data['tenant_cccd']);
                $existingUserWithCccd = User::where('cccd_number', $inputCccd)
                    ->where('id', '!=', $tenant->id)
                    ->first();

                if ($existingUserWithCccd) {
                    throw new \Exception("Số CCCD {$inputCccd} đã được đăng ký cho tài khoản khác trên hệ thống ({$existingUserWithCccd->name} - SĐT: {$existingUserWithCccd->phone}). Vui lòng kiểm tra lại!");
                }

                $tenant->update(['cccd_number' => $inputCccd]);
                $tenant->refresh();
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
            //4. Upload nhiều trang ảnh hợp đồng & tự động nối thành 01 file PDF duy nhất
            $fileList = is_array($files) ? $files : ($files ? [$files] : []);
            $filePath = null;

            if (!empty($fileList)) {
                $htmlPages = [];
                foreach ($fileList as $f) {
                    if (!$f) continue;
                    $extension = strtolower($f->getClientOriginalExtension());
                    if (in_array($extension, ['jpg', 'png', 'jpeg'])) {
                        $imageData = base64_encode(file_get_contents($f->getRealPath()));
                        $mimeType = $f->getMimeType();
                        $src = 'data:' . $mimeType . ';base64,' . $imageData;
                        $htmlPages[] = '<div class="page"><img src="' . $src . '" /></div>';
                    } else if ($extension === 'pdf' && count($fileList) === 1) {
                        $filePath = $f->store('contracts/documents', 'r2_private');
                        break;
                    }
                }

                if (!empty($htmlPages) && !$filePath) {
                    $fullHtml = '<html><head><style>
                        @page { margin: 0px; }
                        body { margin: 0; padding: 0; text-align: center; background-color: #ffffff; }
                        .page { page-break-after: always; width: 100%; text-align: center; }
                        .page:last-child { page-break-after: avoid; }
                        img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
                    </style></head><body>' . implode('', $htmlPages) . '</body></html>';

                    $pdf = Pdf::loadHTML($fullHtml);
                    $fileName = 'contracts/documents/' . uniqid() . '.pdf';
                    Storage::disk('r2_private')->put($fileName, $pdf->output());
                    $filePath = $fileName;
                }
            }

            if (!$filePath) {
                throw new \Exception('Không thể tải file hợp đồng lên. Vui lòng chọn ít nhất 1 trang ảnh hoặc tệp PDF.');
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
                'entry_elec_index' => isset($data['entry_elec_index']) && $data['entry_elec_index'] !== '' ? (int) $data['entry_elec_index'] : null,
                'entry_water_index' => isset($data['entry_water_index']) && $data['entry_water_index'] !== '' ? (int) $data['entry_water_index'] : null,
                'entry_readings_submitted_at' => (isset($data['entry_elec_index']) || isset($data['entry_water_index'])) ? now() : null,
            ]);
            //check xem hợp đồng này đã có hợp động hiệu lực trước đó chưa
            $hasExistingPrimaryContract = Contract::where('room_id', $room->id)
                ->where('tenant_id', '!=', $tenant->id)
                ->whereIn('status', ['signed', 'active'])
                ->exists();
            // Nếu người này vốn đang ở ghép trong phòng -> Đổi trạng thái resident sang inactive (vì đã trở thành Chủ HĐ chính)
            if ($isAlreadyResident) {
                RoomResident::where('room_id', $room->id)
                    ->where('user_id', $tenant->id)
                    ->update([
                        'status' => 'inactive',
                        'end_date' => now()->format('Y-m-d')
                    ]);
            }

            if ($hasExistingPrimaryContract || $room->current_people > 0) {
                $newPeopleCount = min($room->capacity, max(1, $room->current_people + ($isAlreadyResident ? 0 : 1)));
            } else {
                $newPeopleCount = min($room->capacity, max(1, $numberOfTenants));
            }
            //cập nhật trạng thái phòng trọ và số người ở hiện tại
            $room->update([
                'status' => 'rented',
                'current_people' => $newPeopleCount
            ]);

            RoomPost::where('room_id', $room->id)->update(['status' => 'hidden']);
            
            // Cập nhật trạng thái lịch hẹn:
            // - Nếu là Cư dân ở ghép lên làm Chủ hợp đồng -> became_main_tenant (Đã đứng tên Hợp đồng)
            // - Nếu là Thuê mới -> success_matched (Đã ký Hợp đồng & Đóng cọc)
            $newApptStatus = $isAlreadyResident ? 'became_main_tenant' : 'success_matched';
            \App\Models\Appointment::where('room_id', $room->id)
                ->where('user_id', $tenant->id)
                ->update(['status' => $newApptStatus]);
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
            $refundAmount = 0;
            if ($data['deposit_handling'] === 'refund_full') {
                $refundAmount = $contract->deposit_amount;
            } else if ($data['deposit_handling'] === 'refund_partial') {
                $refundAmount = $data['deposit_refund_amount'] ?? 0;
            } else if ($data['deposit_handling'] === 'keep_deposit') {
                $refundAmount = 0;
            }
            $contract->update([
                'status' => 'terminated',
                'liquidated_at' => now(),
                'deposit_handling' => $data['deposit_handling'],
                'deposit_refund_amount' => $data['deposit_refund_amount'] ?? 0,
                'cancellation_reason' => $data['notes'] ?? 'Chủ trọ thanh lý hợp đồng.',
            ]);
            //gửi thông báo tới clien
            $tenant = $contract->tenant;
            if ($tenant) {
                $roomNum = $contract->room->room_number ?? '';
                $handlingText = 'Hoàn trả cọc';
                if ($contract->deposit_handling === 'keep_deposit') {
                    $handlingText = 'Tịch thu / Mất cọc (do vi phạm hoặc chấm dứt hợp đồng trước thời hạn)';
                } else if ($contract->deposit_handling === 'refund_partial') {
                    $handlingText = 'Khấu trừ một phần cọc (' . number_format($contract->deposit_refund_amount) . 'đ)';
                } else if ($contract->deposit_handling === 'refund_full') {
                    $handlingText = 'Hoàn trả 100% tiền cọc (' . number_format($contract->deposit_amount) . 'đ)';
                }
                $reasonText = !empty($data['notes']) ? " Ghi chú / Lý do: {$data['notes']}" : '';
                // bắn thông báo
                $tenant->notify(new \App\Notifications\AdminNotification(
                    'Hợp đồng thuê trọ đã được thanh lý',
                    "Hợp đồng thuê phòng {$roomNum} của bạn đã được chủ trọ thực hiện thanh lý. Phương án xử lý cọc: {$handlingText}. {$reasonText}",
                    route('quanlynoio')
                ));
            }
            // Chuyển trạng thái lịch hẹn của người dùng này sang terminated (Hợp đồng đã thanh lý)
            \App\Models\Appointment::where('room_id', $contract->room_id)
                ->where('user_id', $contract->tenant_id)
                ->update(['status' => 'terminated']);

            // chuyển trạng thái user của người thanh lý sang inactive
            RoomResident::where('room_id', $contract->room_id)
                ->where('user_id', $contract->tenant_id)
                ->update([
                    'status' => 'inactive',
                    'end_date' => now()->format('Y-m-d')
                ]);
            $room = $contract->room;
            if ($room) {
                //đếm lại số hợp đồng còn hiệu lực
                $remainingContracts = Contract::where('room_id', $room->id)
                    ->whereIn('status', ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'])
                    ->count();
                //đếm số cư dân còn hoạt động
                $remainingResidents = RoomResident::where('room_id', $room->id)
                    ->where('status', 'active')
                    ->count();
                $newCurrentPeople = max($remainingContracts, $remainingResidents);
                $newRoomStatus = $newCurrentPeople > 0 ? 'rented' : 'available';
                $room->update([
                    'status' => $newRoomStatus,
                    'current_people' => $newCurrentPeople
                ]);
                if ($newRoomStatus === 'available') {
                    RoomPost::where('room_id', $room->id)->update(['status' => 'approved']);
                }
                event(new \App\Events\RoomStatusUpdated($room->id, $newRoomStatus));
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
            //lưu lại lịch sử gia hạn hợp đồng
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
            //cập nhật ngày kết thúc hợp đồng và đưa trạng thái về Active
            Contract::$allowImmutableUpdate = true;
            $contract->update([
                'end_date' => $data['new_end_date'],
                'status' => 'active',
                'cancellation_reason' => null,
            ]);
            Contract::$allowImmutableUpdate = false;
            //gửi thông báo phản hồi về cho user
            if ($tenant) {
                $newEndDateFormatted = date('d/m/Y', strtotime($data['new_end_date']));
                $roomNum = $contract->room->room_number ?? '';
                $tenant->notify(new \App\Notifications\AdminNotification(
                    'Hợp đồng đã đượ gia hạn thành công',
                    "Hợp đồng thuê phòng {$roomNum} của bạn đã được chủ trọ phê duyệt gia hạn đến ngày {$newEndDateFormatted}.",
                    route('quanlynoio')
                ));
            }
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
                // luồng 2: Giới thiệu người quen -> Thêm thành viên vào phòng 
                $user = null;
                if (!empty($request->new_resident_email)) {
                    $user = User::where('email', $request->new_resident_email)->first();
                }
                if (!$user && !empty($request->new_resident_phone)) {
                    $user = User::where('phone', $request->new_resident_phone)->first();
                }
                if (!$user && !empty($request->new_resident_cccd)) {
                    $user = User::where('cccd_number', $request->new_resident_cccd)->first();
                }

                // Nếu B chưa có tài khoản trên hệ thống -> Tự động khởi tạo tài khoản mới cho B
                if (!$user) {
                    $user = User::create([
                        'name' => $request->new_resident_name ?: 'Thành viên ở ghép',
                        'phone' => $request->new_resident_phone,
                        'email' => $request->new_resident_email,
                        'cccd_number' => $request->new_resident_cccd,
                        'role' => 'tenant',
                        'password' => \Illuminate\Support\Facades\Hash::make('12345678'), // Mật khẩu mặc định, B có thể tự đổi / tạo lại sau
                    ]);
                } else {
                    // Cập nhật SĐT và CCCD nếu tài khoản B đã tồn tại nhưng còn thiếu
                    $userDataToUpdate = [];
                    if (empty($user->phone) && !empty($request->new_resident_phone)) {
                        $userDataToUpdate['phone'] = $request->new_resident_phone;
                    }
                    if (empty($user->cccd_number) && !empty($request->new_resident_cccd)) {
                        $userDataToUpdate['cccd_number'] = $request->new_resident_cccd;
                    }
                    if (!empty($userDataToUpdate)) {
                        $user->update($userDataToUpdate);
                    }
                }
                RoomResident::create([
                    'room_id' => $request->room_id,
                    'user_id' => $user->id,
                    'start_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]);

                //gửi thông báo cho người ở ghép được duyệt vào ở ghép
                if ($user) {
                    $roomNum = $request->room->room_number ?? '';
                    $houseName = $request->room->boardingHouse->name ?? 'Nhà trọ';
                    $user->notify(new \App\Notifications\AdminNotification(
                        'Yêu cầu ở ghép đã được duyệt',
                        "Chúc mừng! Bạn đã được chủ trọ phê duyệt chính thức trở thành thành viên ở ghép tại phòng {$roomNum} ({$houseName}).",
                        'success',
                        route('quanlynoio')
                    ));
                }
                if ($request->room) {
                    $hasActiveContract = Contract::where('room_id', $request->room_id)
                        ->whereIn('status', ['active', 'signed', 'awaiting_upload', 'termination_requested', 'expiring'])
                        ->exists();
                    $activeResidentsCount = RoomResident::where('room_id', $request->room_id)
                        ->where('status', 'active')
                        ->count();
                    $newCurrentPeople = max(1, ($hasActiveContract ? 1 : 0) + $activeResidentsCount);

                    $request->room->update(['current_people' => $newCurrentPeople]);
                    $request->room->current_people = $newCurrentPeople;

                    // Nếu số người đã đạt/vượt quá sức chứa -> Tự động đánh dấu tin đăng là đã cho thuê hết
                    if ($newCurrentPeople >= $request->room->capacity) {
                        \App\Models\RoomPost::where('room_id', $request->room_id)->update(['status' => 'rented']);
                    }
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
            $typeLabel = $request->type === 'stranger' ? 'Tìm người ở ghép (người lạ)' : 'Giới thiệu người ở ghép';
            $tenant->notify(new \App\Notifications\AdminNotification(
                'Yêu cầu ở ghép bị từ chối',
                "Yêu cầu '{$typeLabel}' cho phòng của bạn đã bị chủ trọ từ chối.",
                route('quanlynoio')
            ));
        }
        return $request;
    }

    //chuyển giao hợp hợp đồng cho thành viên ở ghép
    public function transferContractToResident(Contract $oldContract, int $newTenantUserId, array $data, int $landlord)
    {
        return DB::transaction(function () use ($oldContract, $newTenantUserId, $data, $landlord) {
            $newTenant = User::findOrFail($newTenantUserId);
            //kết thúc hợp đồng cũ của user A
            Contract::$allowImmutableUpdate = true;
            $oldContract->update([
                'status' => 'completed',
                'cancellation_reason' => "Chuyển giao quyền chủ hợp đồng cho thành viên {$newTenant->name}.",
            ]);
            Contract::$allowImmutableUpdate = false;
            //chuyển trạng thái cư dân user B ở ghép sang inactive lên làm chủ hợp đồng
            RoomResident::where('room_id', $oldContract->room_id)
                ->where('user_id', $newTenantUserId)
                ->update(['status' => 'inactive', 'end_date' => now()->format('Y-m-d')]);
            //tạo hợp đồng mới đứng tên user B
            $newContract = Contract::create([
                'room_id' => $oldContract->room_id,
                'tenant_id' => $newTenantUserId,
                'start_date' => $data['start_date'] ?? now()->format('Y-m-d'),
                'end_date' => $data['end_date'],
                'monthly_rent' => $data['monthly_rent'] ?? $oldContract->monthly_rent,
                'deposit_amount' => $data['deposit_amount'] ?? $oldContract->deposit_amount,
                'status' => 'active',
                'created_by' => $landlord,
            ]);
            //gửi thông báo cho User A
            if ($oldContract->tenant) {
                $oldContract->tenant->notify(new \App\Notifications\AdminNotification(
                    'Chuyển giao hợp đồng thành công',
                    "Hợp đồng thuê phòng của bạn đã được chuyển giao thành công cho thành viên {$newTenant->name}.",
                    route('quanlynoio')
                ));
            }
            //gửi thông báo cho User B 
            $newTenant->notify(new \App\Notifications\AdminNotification(
                'Chúc mừng! Bạn đã trở thành Chủ hợp đồng mới',
                "Bạn đã được chuyển đứng tên chủ hợp đồng chính cho phòng trọ.",
                route('quanlynoio')
            ));

            //đếm lại số lượng người ở thực tế của phòng sau khi chuyển giao hợp đồng
            $room = $oldContract->room;
            if ($room) {
                $activeContracts = Contract::where('room_id', $room->id)
                    ->whereIn('status', ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'])
                    ->count();
                $activeResidents = RoomResident::where('room_id', $room->id)
                    ->where('status', 'active')
                    ->count();
                $newCurrentPeople = max($activeContracts, $activeResidents);
                $room->update([
                    'status' => 'rented',
                    'current_people' => $newCurrentPeople
                ]);
            }
            return $newContract;
        });
    }
}

?>