<?php
namespace App\Services;

use App\Models\RoomResident;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\RoommateRequest;
use App\Models\Contract;

class ProfileService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Lấy dữ liệu bổ sung cho trang cá nhân
     */
    public function getProfileData(User $user): array
    {
        $hasContract = \Illuminate\Support\Facades\DB::table('contracts')
            ->where('tenant_id', $user->id)
            ->whereIn('status', ['signed', 'active'])
            ->exists();

        $isResident = \Illuminate\Support\Facades\DB::table('room_residents')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        $rentalStatus = 'Chưa thuê trọ';
        if ($hasContract) {
            $rentalStatus = 'Đang thuê trọ';
        } else if ($isResident) {
            $rentalStatus = 'Đang ở ghép';
        }

        return [
            'rentalStatus' => $rentalStatus,
            'accountStatus' => $user->status === 'active' ? 'Đang hoạt động' : 'Bị khóa',
            'canUpdateProfile' => true,
            'daysUntilNextUpdate' => 0,
        ];
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(User $user, array $data): ?User
    {
        // Chặn cập nhật nếu đã từng cập nhật thông tin 1 lần trước đó
        if (!is_null($user->last_profile_update_at)) {
            throw new \Exception('Bạn chỉ được phép cập nhật thông tin cá nhân 1 lần duy nhất để bảo đảm tính pháp lý hợp đồng. Vui lòng liên hệ Admin nếu cần hỗ trợ thay đổi!');
        }

        // 1. Số điện thoại: Chỉ cho phép nhập 1 lần duy nhất khi đang trống
        if (!empty($user->phone)) {
            unset($data['phone']);
        }

        // 2. Số CCCD: Chỉ cho phép nhập 1 lần duy nhất khi đang trống
        if (!empty($user->cccd_number) && strlen(trim($user->cccd_number)) === 12) {
            unset($data['cccd_number']);
        }

        // Lưu thông tin cũ để so sánh
        $oldInfo = [
            'name' => $user->name,
            'address' => $user->address,
            'job' => $user->job,
            'dob' => $user->dob ? $user->dob->format('Y-m-d') : null,
            'gender' => $user->gender,
            'cccd_number' => $user->cccd_number,
        ];

        // Cập nhật thời gian đổi thông tin lần cuối
        $data['last_profile_update_at'] = \Carbon\Carbon::now();
        $updatedUser = $this->userRepository->updateUser($user->id, $data);

        // Kiểm tra xem có thực sự thay đổi thông tin không
        $hasChanges = false;
        foreach ($oldInfo as $key => $oldValue) {
            $newValue = $data[$key] ?? null;
            if ($oldValue !== $newValue) {
                $hasChanges = true;
                break;
            }
        }

        if ($hasChanges) {
            // Lấy danh sách ID chủ trọ đang có hợp đồng ký kết (signed) với người dùng này
            $landlordIds = \Illuminate\Support\Facades\DB::table('contracts')
                ->join('rooms', 'contracts.room_id', '=', 'rooms.id')
                ->join('boarding_houses', 'rooms.boarding_house_id', '=', 'boarding_houses.id')
                ->where('contracts.tenant_id', $user->id)
                ->where('contracts.status', 'signed')
                ->pluck('boarding_houses.user_id')
                ->unique()
                ->toArray();

            if (!empty($landlordIds)) {
                $landlords = \App\Models\User::whereIn('id', $landlordIds)->get();
                \Illuminate\Support\Facades\Notification::send($landlords, new \App\Notifications\TenantProfileUpdated($updatedUser, $oldInfo));
            }
        }

        return $updatedUser;
    }

    /**
     * Cập nhật ảnh đại diện
     */
    public function updateAvatar(User $user, $avatarFile): string
    {
        $useR2 = config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret');
        $r2Url = rtrim(config('filesystems.disks.r2_public.url') ?? env('CLOUDFLARE_R2_PUBLIC_URL', ''), '/');
        $urlPath = '';

        // Xóa ảnh cũ nếu có
        if ($user->avatar) {
            try {
                $oldPath = str_replace('/storage/', '', parse_url($user->avatar, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            } catch (\Throwable $e) {}
        }

        if ($useR2 && !empty($r2Url)) {
            try {
                $path = $avatarFile->store('avatars', 'r2_public');
                $urlPath = str_starts_with($path, 'http') ? $path : $r2Url . '/' . ltrim($path, '/');
            } catch (\Throwable $e) {}
        }

        if (empty($urlPath)) {
            $path = $avatarFile->store('avatars', 'public');
            $urlPath = '/storage/' . ltrim($path, '/');
        }

        // Cập nhật database
        $this->userRepository->updateUser($user->id, ['avatar' => $urlPath]);

        return $urlPath;
    }

    /**
     * Tạo yêu cầu tìm người lạ ở ghép (Stranger)
     */
    public function createStrangerRequest(User $user): void
    {
        //1. Tìm hợp đồng của người đứng tên đại diện
        $contract = Contract::where('tenant_id', $user->id)
            ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
            ->first();
        //2. Nếu không phải người đại diện, check xem có phải là thành viên ở ghép không
        if (!$contract) {
            $resident = RoomResident::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
            if ($resident) {
                $contract = Contract::where('room_id', $resident->room_id)
                    ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
                    ->first();
            }
        }
        if (!$contract) {
            throw new \Exception('Bạn không có hợp đồng thuê trọ nào đang hoạt động.');
        }
        $exists = RoommateRequest::where('room_id', $contract->room_id)
            ->where('tenant_id', $user->id)
            ->where('type', 'stranger')
            ->where('status', 'pending')
            ->exists();
        if ($exists) {
            throw new \Exception('Yêu cầu tìm người ở ghép của bạn đang chờ chủ trọ phê duyệt.');
        }
        RoommateRequest::create([
            'room_id' => $contract->room_id,
            'tenant_id' => $user->id,
            'type' => 'stranger',
            'status' => 'pending'
        ]);
        $landlord = $contract->room->boardingHouse->user ?? null;
        if ($landlord) {
            $roomNum = $contract->room->room_number ?? '';
            $landlord->notify(new \App\Notifications\AdminNotification(
                'Yêu cầu tìm người ở ghép mới',
                "Khách thuê tại phòng {$roomNum} gửi yêu cầu đăng tin tìm người ở ghép.",
                'info',
                route('landlord.roommate-requests')
            ));
        }
    }

    /**
     * Tạo yêu cầu giới thiệu người quen ở ghép (Acquaintance)
     */
    public function createAcquaintanceRequest(User $user, array $data): void
    {
        // 1. Tìm hợp đồng của người dùng đứng tên đại diện
        $contract = Contract::where('tenant_id', $user->id)
            ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
            ->first();
        // 2. Nếu không phải người đại diện, kiểm tra xem có phải thành viên ở ghép không
        if (!$contract) {
            $resident = RoomResident::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
            if ($resident) {
                $contract = Contract::where('room_id', $resident->room_id)
                    ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
                    ->first();
            }
        }
        if (!$contract) {
            throw new \Exception('Bạn không có hợp đồng thuê trọ nào đang hoạt động.');
        }
        $exists = RoommateRequest::where('room_id', $contract->room_id)
            ->where('tenant_id', $user->id)
            ->where('type', 'acquaintance')
            ->where('status', 'pending')
            ->exists();
        if ($exists) {
            throw new \Exception('Bạn đang có một yêu cầu giới thiệu người quen chờ chủ trọ duyệt.');
        }
        //tìm tài khoản trong hệ thống nếu có
        $userB = \App\Models\User::where('email', $data['new_resident_email'])
            ->orWhere('phone', $data['new_resident_phone'])
            ->first();
        if ($userB) {
            //check B đã đứng tên chủ hợp đồng ở phòng khác chưa
            $isOwnerElsewhere = \App\Models\Contract::where('tenant_id', $userB->id)
                ->whereIn('status', ['active', 'signed', 'awaiting_upload', 'expiring'])
                ->exists();
            if ($isOwnerElsewhere) {
                throw new \Exception('Thành viên này hiện đang là chủ hợp đồng thuê trọ tại một phòng khác!');
            }
            //check B đã là thành viên ở ghép phòng khác chưa
            $isOwnerElsewhere = \App\Models\RoomResident::where('user_id', $userB->id)
                ->where('status', 'active')
                ->where('room_id', '!=', $contract->room_id)
                ->exists();
            if ($isOwnerElsewhere) {
                throw new \Exception('Thành viên này hiện đang ở ghép tại một phòng trọ khác!');
            }
        }
        // Kiểm tra xem thành viên này đã ở ghép trong phòng chưa
        $alreadyResident = RoomResident::where('room_id', $contract->room_id)
            ->whereHas('user', function ($q) use ($data) {
                $q->where('email', $data['new_resident_email'])
                    ->orWhere('phone', $data['new_resident_phone']);
                if (!empty($data['new_resident_cccd'])) {
                    $q->orWhere('cccd_number', $data['new_resident_cccd']);
                }
            })
            ->where('status', 'active')
            ->exists();

        if ($alreadyResident) {
            throw new \Exception('Thành viên này hiện đã đang có tên trong danh sách ở ghép của phòng!');
        }

        RoommateRequest::create([
            'room_id' => $contract->room_id,
            'tenant_id' => $user->id,
            'type' => 'acquaintance',
            'status' => 'pending',
            'new_resident_name' => $data['new_resident_name'],
            'new_resident_phone' => $data['new_resident_phone'],
            'new_resident_email' => $data['new_resident_email'],
            'new_resident_cccd' => $data['new_resident_cccd'],
        ]);
        //tự động nạp SĐT và CCCD này vào tài khoản của User B (nếu user B đã đăng ký tài khoản trước đó)
        $existsUserB = \App\Models\User::where(
            'email',
            $data['new_resident_email']
        )
            ->orWhere('phone', $data['new_resident_phone'])
            ->first();
        if ($existsUserB) {
            $roomNum = $contract->room->room_number ?? '';
            $houseName = $contract->room->boardingHouse->name ?? 'Nhà trọ';
            $existsUserB->notify(new \App\Notifications\AdminNotification(
                'Lời mời ở ghép mới',
                "Bạn {$user->name} đã gửi thông tin giới thiệu bạn vào ở ghép tại phòng {$roomNum}
            ({$houseName}). Vui lòng chờ chủ trọ phê duyệt.",
                'info',
                route('quanlynoio')
            ));
            $userDataToUpdate = [];
            if (empty($existsUserB->phone) && !empty($data['new_resident_phone'])) {
                $userDataToUpdate['phone'] = $data['new_resident_phone'];
            }
            if (empty($existsUserB->cccd_number) && !empty($data['new_resident_cccd'])) {
                $userDataToUpdate['cccd_number'] = $data['new_resident_cccd'];
            }
            if (!empty($userDataToUpdate)) {
                $existsUserB->update($userDataToUpdate);
            }
        }

        $landlord = $contract->room->boardingHouse->user ?? null;
        if ($landlord) {
            $roomNum = $contract->room->room_number ?? '';
            $landlord->notify(new \App\Notifications\AdminNotification(
                'Giới thiệu thành viên ở ghép mới',
                "Khách thuê tại phòng {$roomNum} giới thiệu thành viên mới: {$data['new_resident_name']} vào ở ghép.",
                'info',
                route('landlord.roommate-requests')
            ));
        }
    }
}
