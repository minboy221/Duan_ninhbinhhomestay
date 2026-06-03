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
        
        return [
            'rentalStatus' => $isRenting ? 'Đang thuê' : 'Chưa thuê trọ',
            'accountStatus' => $user->status === 'active' ? 'Đang hoạt động' : 'Bị khóa',
        ];
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(User $user, array $data): ?User
    {
        return $this->userRepository->updateUser($user->id, $data);
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
