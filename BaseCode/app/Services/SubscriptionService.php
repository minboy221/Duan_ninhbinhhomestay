<?php
namespace App\Services;

use App\Models\LandlordSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Notifications\SubscriptionNotification;

class SubscriptionService
{
    //tự động cấp gói 60 ngày Full VIP cho chủ trọ tạo tài khoản mới
    public function assignFreeTrial(User $user)
    {
        $existing = LandlordSubscription::where('user_id', $user->id)->exists();
        if($existing){
            return null;
        }
        //tìm gói dùng thử
        $trialPlan = SubscriptionPlan::where('price',0)->where('is_active', true)
        ->orderBy('sort_order','asc')
        ->first();
        if(!$trialPlan){
            return null;
        }
        //Đọc số ngày dùng thử động do Admin đã cấu hình trên gói
        $durationDays = $trialPlan->duration_days > 0 ? (int)$trialPlan->duration_days : 60;
        //kích hoạt gói dùng thử theo đúng số ngày Admin cài đặt
        $sub = LandlordSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $trialPlan->id,
            'payment_code' => 'TRIAL_' .  strtoupper(\Illuminate\Support\Str::random(8)),
            'price_at_purchase' => 0,
            'start_date' => now()->toDateString(),
            'payment_method' => 'free_trial',
            'status' => 'active',
            'admin_note' => "Hệ thống tự động kích hoạt {$durationDays} ngày dùng thử miễn phí.",
        ]);
        //gửi thông báo cho tài khoản chủ trọ mới
        $user->notify(new SubscriptionNotification("🎉 Chúc Mừng 60 ngày dùng thử VIP!","Tài khoản của bạn được tự động tặng {$durationDays} ngày sử dụng Miễn Phí 100% gói Full VIP cao cấp!",
        route('landlord.subscriptions.index'),
        'success'
        ));
        return $sub;
    }

    //lấy giá trị tính năng của chủ trọ dựa trên gói đang Active
    public function getFeatureValue(User $user, string $featureCode, $default = null)
    {
        $activeSub = $user->activeSubscription;
        if (!$activeSub || !$activeSub->plan) {
            return $default;
        }
        $feature = $activeSub->plan->features->where('feature_code', $featureCode)->first();
        return $feature ? $feature->pivot->feature_value : $default;
    }

    //tạo đơn mua gói mới (trạng thái là pending kèm theo VietQR)
    public function createPendingSubscription(User $user, SubscriptionPlan $plan): LandlordSubscription
    {
        //sing mã giao dịch độc nhất
        $paymentCode = 'SUB' . rand(10000, 99999) . $user->id;
        return LandlordSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_code' => $paymentCode,
            'price_at_purchase' => $plan->price,
            'payment_method' => 'vietqr',
            'status' => 'pending',
        ]);
    }

    //duyệt gói (chuyển sang active và tính ngày)
    public function activateSubscription(LandlordSubscription $sub, ?User $approveBy = null): bool
    {
        $plan = $sub->plan;
        if (!$plan)
            return false;
        $startDate = now();
        $endDate = ($plan->duration_days == -1) ? null : now()->addDays($plan->duration_days)->toDateString();
        //huỷ các gói pending khác của user này nếu có
        LandlordSubscription::where('user_id', $sub->user_id)
            ->where('id', '!=', $sub->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected', 'admin_note' => 'Đã chọn đăng ký hói khác']);
        //chuyển các gói active cũ thành expired
        LandlordSubscription::where('user_id', $sub->user_id)
            ->where('id', '!=', $sub->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);
        $sub->update([
            'status' => 'active',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate,
            'approved_by' => $approveBy ? $approveBy->id : null,
            'approved_at' => now(),
        ]);
        return true;
    }
    //hàm check và tự động kích hoạt gói miễn phí nếu chủ trọ chưa có gói nào hoặc gói đã hết hạn
    public function ensureActiveSubscription(User $user){
        //check xem chủ trọ có gói nào đang active và chưa hết hạn không
        $activeSub = $user->activeSubscription;
        if($activeSub){
            return $activeSub;
        }
        //nếu gói cũ đã hết hạn -> đổi trạng thái gói cũ thành 'expired'
        LandlordSubscription::where('user_id', $user->id)
        ->where('status', 'active')
        ->update(['status' => 'expired']);
        //tìm gói miễn phí (price = 0) trong hệ thống
        $freePlan = SubscriptionPlan::where('price', 0)->where('is_active', true)->first();
        if(!$freePlan){
            return null;
        }
        //tự động tạo và kích hoạt gói miễn phí mới cho chủ trọ
        return LandlordSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'payment_code' => 'FREE_' . strtoupper(\Illuminate\Support\Str::random(8)),
            'price_at_purchase' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'payment_method' => 'default_free',
            'status' => 'active',
            'admin_note' => 'Hệ thống tự động chuyển về gói Miễn Phí mặc định do gói cũ đã hết hạn.',
        ]);
    }
}
?>