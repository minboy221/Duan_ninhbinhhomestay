<?php
namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerAccount(array $data)
    {
        $cleanEmail = strtolower(trim($data['email']));
        // Kiểm tra bảo vệ tầng 2
        if (User::where('email', $cleanEmail)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Địa chỉ email này đã được đăng ký sử dụng trên hệ thống.',
            ]);
        }
        $data['email'] = $cleanEmail;
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->create($data);
        // Tự động khớp vào phòng trọ khi đăng ký tài khoản
        $this->autoClaimRoommateRequests($user);
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

    // Tự quét và thêm user mới vào phòng nếu có yêu cầu ở ghép khớp với Email
    public function autoClaimRoommateRequests(User $user): void
    {
        // Quét tìm các yêu cầu ở ghép trùng Email vừa đăng ký
        $requests = \App\Models\RoommateRequest::where(function ($q) use ($user) {
            $q->where('new_resident_email', $user->email);
        })->whereIn('status', ['pending', 'approved'])->get();

        foreach ($requests as $req) {
            // 1. Tự động nạp SĐT và CCCD do User A nhập vào Tài khoản của User B
            $userDataToUpdate = [];
            if (empty($user->phone) && !empty($req->new_resident_phone)) {
                $userDataToUpdate['phone'] = $req->new_resident_phone;
            }
            if (empty($user->cccd_number) && !empty($req->new_resident_cccd)) {
                $userDataToUpdate['cccd_number'] = $req->new_resident_cccd;
            }
            if (!empty($userDataToUpdate)) {
                $user->update($userDataToUpdate);
            }

            // 2. Tự động thêm User B vào Cư dân ở ghép của phòng
            \App\Models\RoomResident::firstOrCreate([
                'room_id' => $req->room_id,
                'user_id' => $user->id,
            ], [
                'start_date' => now()->format('Y-m-d'),
                'status' => 'active',
            ]);

            // 3. Tăng số lượng người ở phòng (Khống chế không vượt quá capacity)
            if ($req->room) {
                if ($req->room->current_people < $req->room->capacity) {
                    $req->room->increment('current_people');
                }

                // Nếu phòng đã đầy -> Tự động ẩn tin đăng
                if ($req->room->current_people >= $req->room->capacity) {
                    \App\Models\RoomPost::where('room_id', $req->room_id)->update(['status' => 'hidden']);
                }
            }

            // 4. Đổi trạng thái yêu cầu ở ghép thành đã duyệt
            $req->update(['status' => 'approved']);

            // 5. Gửi thông báo chào mừng cho User B
            $roomNum = $req->room->room_number ?? '';
            $user->notify(new \App\Notifications\AdminNotification(
                'Chào mừng bạn đến với phòng trọ!',
                "Tài khoản của bạn đã được tự động thêm vào danh sách cư dân ở ghép tại phòng {$roomNum}.",
                route('quanlynoio')
            ));
        }
    }
}