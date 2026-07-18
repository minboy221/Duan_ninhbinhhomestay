<?php
namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
            Storage::disk('public')->delete($user->avatar);
        }

        // Lưu ảnh mới
        $path = $avatarFile->store('avatars', 'public');
        
        // Cập nhật database
        $this->userRepository->updateUser($user->id, ['avatar' => $path]);

        return $path;
    }
}
