<?php
namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\UserVerification;

class UserRepository implements UserRepositoryInterface
{
    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function findByGoogleId(string $googleId)
    {
        return User::where('google_id', $googleId)->first();
    }

    public function updateOrCreateGoogleUser($googleUser)
    {
        return User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
            ]
        );
    }

    //phần xử lý cho xác minh thông tin chủ trọ
    //sẽ tìm theo user_id của người dùng khi đăng ký vào website
    public function updateOrCreateVerification($userId, array $data)
    {
        return UserVerification::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    public function updateUser($userId, array $data)
    {
        $user = $this->model->find($userId);

        if ($user) {
            $user->update($data);
            return $user;
        }
        return null;
    }
}
