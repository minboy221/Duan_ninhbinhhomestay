<?php
namespace App\Services;

use App\Models\LandlordSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        LandlordSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $trialPlan->id,
            'payment_code' => 'TRIAL_' .  strtoupper(\Illuminate\Support\Str::random(8)),
            'price_at_purchase' => 0,
            'start_date' => now()->toDateString(),
            'payment_method' => 'free_trial',
            'status' => 'active',
            'admin_note' => "Hệ thống tự động kích hoạt {$durationDays} ngày dùng thử miễn phí.",
        ]);
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
}

?>