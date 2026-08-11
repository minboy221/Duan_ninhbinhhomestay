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
        $isRenting = $this->userRepository->isUserRenting($user->id);

        // Kiểm tra xem người dùng có bị giới hạn 15 ngày đổi thông tin không
        $canUpdateProfile = true;
        $daysUntilNextUpdate = 0;

        if ($user->last_profile_update_at) {
            $lastUpdate = \Carbon\Carbon::parse($user->last_profile_update_at);
            $now = \Carbon\Carbon::now();
            $diffInSeconds = $now->diffInSeconds($lastUpdate);
            $fifteenDaysInSeconds = 15 * 24 * 60 * 60; // 15 ngày tính bằng giây

            if ($diffInSeconds < $fifteenDaysInSeconds) {
                $canUpdateProfile = false;
                $secondsRemaining = $fifteenDaysInSeconds - $diffInSeconds;
                $daysUntilNextUpdate = (int) ceil($secondsRemaining / (24 * 60 * 60)); // Làm tròn lên số ngày còn lại
            }
        }

        return [
            'rentalStatus' => $isRenting ? 'Đang thuê' : 'Chưa thuê trọ',
            'accountStatus' => $user->status === 'active' ? 'Đang hoạt động' : 'Bị khóa',
            'canUpdateProfile' => $canUpdateProfile,
            'daysUntilNextUpdate' => $daysUntilNextUpdate,
        ];
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(User $user, array $data): ?User
    {
        // Chỉ cho phép lưu số điện thoại nếu dữ liệu hiện tại của user đang trống
        if (!empty($user->phone)) {
            unset($data['phone']);
        }

        // Kiểm tra xem đã đủ 15 ngày kể từ lần cập nhật gần nhất chưa
        if ($user->last_profile_update_at) {
            $lastUpdate = \Carbon\Carbon::parse($user->last_profile_update_at);
            $now = \Carbon\Carbon::now();
            $diffInSeconds = $now->diffInSeconds($lastUpdate);
            $fifteenDaysInSeconds = 15 * 24 * 60 * 60;

            if ($diffInSeconds < $fifteenDaysInSeconds) {
                $secondsRemaining = $fifteenDaysInSeconds - $diffInSeconds;
                $daysRemaining = (int) ceil($secondsRemaining / (24 * 60 * 60));

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'profile' => "Bạn chỉ có thể cập nhật thông tin cá nhân 1 lần mỗi 15 ngày. Lần cập nhật tiếp theo khả dụng sau {$daysRemaining} ngày."
                ]);
            }
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
        // Xóa ảnh cũ nếu có
        if ($user->avatar) {
            Storage::disk('r2_public')->delete($user->avatar);
        }

        // Lưu ảnh mới
        $ext = $avatarFile->getClientOriginalExtension();
        $path = $avatarFile->storeAs('avatars', "avatar_user_{$user->id}.{$ext}", 'r2_public');

        // Cập nhật database
        $this->userRepository->updateUser($user->id, ['avatar' => $path]);

        return $path;
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
                route('landlord.roommate-requests')
            ));
        }
    }
}
