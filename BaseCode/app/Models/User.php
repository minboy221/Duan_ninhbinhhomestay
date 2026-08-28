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
        'google_id',
        'last_profile_update_at',
        'bump_credits',
        'package_name',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'last_seen_at',
        'cccd_number',
        'fcm_token'
    ];

    protected $appends = ['is_online', 'has_vip_frame'];

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
        return $this->hasOne(BoardingHouse::class, 'user_id', 'id')->latestOfMany();
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
            })
            // Ưu tiên lấy gói trả phí mới nhất trước gói miễn phí
            ->orderBy('price_at_purchase', 'desc')
            ->orderBy('id', 'desc');
    }

    // Hàm check lấy giá trị của feature_code từ gói dịch vụ đang hoạt động
    public function getFeatureValue(string $featureCode)
    {
        $targetUser = $this;
        //nếu là tài khoản phụ Lấy Chủ Trọ Chính của Cơ sở trọ đang chọn
        $selectedHouseId = session('selected_boarding_house_id');
        if ($selectedHouseId) {
            $house = \App\Models\BoardingHouse::find($selectedHouseId);
            //nếu cơ sở trọ thuộc về chủ trọ chính khác -> lấy gói của chủ trọ chính
            if ($house && $house->user_id !== $this->id) {
                $targetUser = $house->user;
            }
        }
        if (!$targetUser) {
            return null;
        }
        $activeSub = $targetUser->activeSubscription()->with('plan.features')->first();
        // Nếu chủ trọ không có gói active nào -> tự động lấy cấu hình của gói miễn phí
        if (!$activeSub || !$activeSub->plan) {
            $freePlan = \App\Models\SubscriptionPlan::where('price', 0)->with('features')->first();
            if ($freePlan) {
                $feature = $freePlan->features->firstWhere('feature_code', $featureCode);
                return $feature ? $feature->pivot->feature_value : null;
            }
            return null;
        }
        if (!$activeSub->plan->features) {
            return null;
        }
        $feature = $activeSub->plan->features->firstWhere('feature_code', $featureCode);
        return $feature ? $feature->pivot->feature_value : null;
    }

    // Hàm check xem chủ trọ có thể tạo thêm tài nguyên trong hệ thống không
    public function canCreateResource(string $featureCode, int $currentCount): bool
    {
        $limit = $this->getFeatureValue($featureCode);
        if ($limit === null)
            return true;
        if ($limit === '-1' || (int) $limit === -1)
            return true; // Giá trị -1 là vô hạn
        return $currentCount < (int) $limit;
    }

    // Hàm check xem chủ trọ có quyền bật/tắt tính năng hay không
    public function hasFeature(string $featureCode): bool
    {
        $val = $this->getFeatureValue($featureCode);
        return $val === 'true' || $val === true || $val === 'gold';
    }
    // Kiểm tra xem Chủ trọ có được sử dụng Vô Hạn lượt đẩy tin hay không 
    public function hasUnlimitedBump(): bool
    {
        return (string) $this->getFeatureValue('priority_listing') === '-1';
    }

    // Kiểm tra xem Chủ trọ có đang sở hữu Khung VIP hay không
    public function getHasVipFrameAttribute(): bool
    {
        $activeSub = $this->activeSubscription()->with('plan.features')->first();
        if (!$activeSub || !$activeSub->plan) {
            return false;
        }
        $frameFeat = $activeSub->plan->features->firstWhere('feature_code', 'avatar_frame');
        return $frameFeat && in_array($frameFeat->pivot->feature_value, ['gold', 'true', '1']);
    }

    // Check tài khoản của chủ trọ có bị đóng băng do vượt hạn mức của gói không
    public function isRoomFrozen(\App\Models\Room $room): bool
    {
        // 1. Phòng đang được thuê / có người ở -> Không bao giờ bị đóng băng
        if (in_array($room->status, ['occupied', 'rented', 'deposited', 'expiring_soon', 'pending_renewal'])) {
            return false;
        }

        // 2. Lấy hạn mức phòng của gói hiện tại
        $limit = $this->getFeatureValue('max_rooms');
        if ($limit === null || $limit === '-1' || (int) $limit === -1) {
            return false;
        }

        $userId = $this->id;

        // 3. Lấy danh sách ID tất cả các phòng trống (available) của Chủ trọ xếp theo ID tăng dần
        $availableRoomIds = \App\Models\Room::where(function ($query) use ($userId) {
            $query->whereHas('boardingHouse', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->orWhereHas('floor.property', function ($q) use ($userId) {
                $q->where('landlord_id', $userId);
            });
        })->where('status', 'available')
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        // 4. Tìm vị trí thứ tự (1-indexed) của phòng này trong danh sách phòng trống
        $index = array_search($room->id, $availableRoomIds);
        if ($index === false) {
            return false;
        }

        // 5. Nếu thứ tự phòng trống vượt quá hạn mức gói -> Tạm đóng băng!
        return ($index + 1) > (int) $limit;
    }
}
