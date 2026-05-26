<?php
namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerAccount(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->create($data);
        Auth::login($user);
    }

    public function loginAccount(array $credentials)
    {
        unset($credentials['captcha']); // Bỏ captcha ra trước khi check DB
        return Auth::attempt($credentials);
    }

    public function handleGoogleLogin()
    {
        $googleUser = Socialite::driver('google')->user();
        $user = $this->userRepository->updateOrCreateGoogleUser($googleUser);
        Auth::login($user);
    }

    public function logoutAccount()
    {
        Auth::logout();
    }
}