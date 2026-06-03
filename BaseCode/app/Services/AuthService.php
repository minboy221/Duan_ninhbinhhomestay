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
        event(new \Illuminate\Auth\Events\Registered($user));
        Auth::login($user);
        return $user;
    }

    public function loginAccount(array $credentials, $remember = false)
    {
        unset($credentials['captcha']); // Bỏ captcha ra trước khi check DB
        return Auth::attempt($credentials, $remember);
    }

    public function handleGoogleLogin()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        
        $user = $this->userRepository->findByGoogleId($googleUser->getId());

        if (!$user) {
            $user = $this->userRepository->findByEmail($googleUser->getEmail());

            if ($user) {
                $updateData = ['google_id' => $googleUser->getId()];
                if (!$user->email_verified_at) {
                    $updateData['email_verified_at'] = now();
                }
                $this->userRepository->updateUser($user->id, $updateData);
            } else {
                $user = $this->userRepository->create([
                    'name' => $googleUser->getName() ?? 'Google User',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            }
        }

        Auth::login($user, true);
        return $user;
    }

    public function logoutAccount()
    {
        Auth::logout();
    }

    public function requestPasswordOtp($user)
    {
        // Generate a 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Save to database with 15 minutes expiration
        $this->userRepository->updateUser($user->id, [
            'otp_code' => $otp,
            'otp_expires_at' => \Carbon\Carbon::now()->addMinutes(15)
        ]);

        // Send Email
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ChangePasswordOTP($otp));
    }

    public function updatePassword($user, $newPassword, $otp)
    {
        // Verify OTP
        if (!$user->otp_code || $user->otp_code !== $otp || \Carbon\Carbon::now()->greaterThan($user->otp_expires_at)) {
            return false;
        }

        // Update password and clear OTP
        $this->userRepository->updateUser($user->id, [
            'password' => Hash::make($newPassword),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return true;
    }

    public function sendPasswordResetLink(array $data)
    {
        return \Illuminate\Support\Facades\Password::sendResetLink($data);
    }

    public function resetPassword(array $data)
    {
        return \Illuminate\Support\Facades\Password::reset(
            $data,
            function ($user, $password) {
                $this->userRepository->updateUser($user->id, [
                    'password' => Hash::make($password),
                    'remember_token' => \Illuminate\Support\Str::random(60),
                ]);
                event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );
    }
}