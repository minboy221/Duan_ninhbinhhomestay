<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserVerification;
use App\Models\RoomPost;
use App\Models\BoardingHouse;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'job',
        'dob',
        'gender',
        'avatar',
        'password',
        'role',
        'status',
        'google_id',
        'otp_code',
        'otp_expires_at',
        'last_profile_update_at',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'last_seen_at',
        'cccd_number',
        'fcm_token'
    ];

    protected $appends = ['is_online'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isLandlord(): bool
    {
        return $this->role === 'landlord';
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_profile_update_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Send the email verification notification.
     * Overridden to send OTP code instead of default link.
     */
    public function sendEmailVerificationNotification()
    {
        $this->otp_code = (string) random_int(100000, 999999);
        $this->otp_expires_at = now()->addMinutes(15);
        $this->save();

        \Illuminate\Support\Facades\Mail::to($this->email)->send(new \App\Mail\VerifyEmailOTP($this->otp_code));
    }
    //xác minh thông tin chủ trọ

    public function verification()
    {
        // Liên kết với bảng user_verifications thông qua cột user_id
        return $this->hasOne(UserVerification::class, 'user_id', 'id');
    }

    public function boardingHouse()
    {
        return $this->hasOne(BoardingHouse::class, 'user_id', 'id');
    }

    /**
     * Relationship to favorited rooms
     */
    public function favoriteRooms()
    {
        return $this->belongsToMany(Room::class, 'favorites', 'user_id', 'room_id')->withTimestamps();
    }
    public function roomPosts()
    {
        return $this->hasMany(RoomPost::class, 'landlord_id', 'id');
    }

    //kiểm tra người dùng có đang online hay không
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }
        //so sánh thời gian hoạt động cuối dùng có lớn hơn thời điểm cách đây 3 phút
        return $this->last_seen_at->gt(now()->subMinutes(3));
    }

    public function propertyManagers()
    {
        return $this->hasMany(PropertyManager::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(LandlordSubscription::class);
    }
    public function activeSubscription()
    {
        return $this->hasOne(LandlordSubscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })->latestOfMany();
    }

    //hàm check lấy giá trị của feature_code từ gói dịch vụ đang hoạt động
    public function getFeatureValue(string $featureCode)
    {
        $activeSub = $this->activeSubscription()->with('plan.features')->first();
        if (!$activeSub || !$activeSub->plan) {
            return null;
        }
        $feature = $activeSub->plan->features->firstWhere('feature_code', $featureCode);
        return $feature ? $feature->pivot->feature_value : null;
    }

    //hàm check xem chủ trọ có thể tạo thêm tài nguyên trong hệ thống không
    public function canCreateResource(string $featureCode, int $currentCount): bool
    {
        $limit = $this->getFeatureValue($featureCode);
        if ($limit === null)
            return true;
        if ($limit === '-1' || (int) $limit === -1)
            return true; //giá trị -1 là vô hạn
        return $currentCount < (int) $limit;
    }

    //hàm check xem chủ trọ có quyền bật/tắt tính năng hay không
    public function hasFeature(string $featureCode): bool
    {
        $val = $this->getFeatureValue($featureCode);
        return $val === 'true' || $val === true || $val === 'gold';
    }

    //check tài khoản của chủ trọ có bị đóng băng do vượt hạn mức của gói không
    public function isRoomFrozen(int $roomIndex): bool
    {
        $limit = $this->getFeatureValue('max_rooms');
        if ($limit === null || $limit === '-1' || (int) $limit === -1) {
            return false; //gói vô hạn không bị đóng băng
        }
        //nếu thứ tự phòng lớn hơn giới hạn gói -> bị đóng băng
        return $roomIndex > (int) $limit;
    }
}
