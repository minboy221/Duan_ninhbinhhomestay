<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseSubscriptionRequest;
use App\Http\Requests\UploadProofRequest;
use App\Notifications\SubscriptionNotification;
use App\Models\LandlordSubscription;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use App\Services\SubscriptionService;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    // Phần hiển thị danh sách các gói cho chủ trọ
    public function index()
    {
        $user = auth()->user();
        // Tự động cấp dùng thử 60 ngày nếu chủ trọ mới chưa có gói
        $service = app(SubscriptionService::class);
        $service->assignFreeTrial($user);
        // Tự động check nếu hết hạn gói -> tự động kích hoạt gói miễn phí
        $service->ensureActiveSubscription($user);
        // Lấy gói active hiện tại
        $activeSubscription = $user->activeSubscription()->with('plan.features')->first();
        // Tính số ngày còn lại
        $daysRemaining = 0;
        if ($activeSubscription) {
            if (!$activeSubscription->end_date && $activeSubscription->plan && $activeSubscription->plan->duration_days > 0) {
                $calculatedEnd = \Carbon\Carbon::parse($activeSubscription->start_date ?? now())
                    ->addDays($activeSubscription->plan->duration_days)
                    ->toDateString();
                $activeSubscription->update(['end_date' => $calculatedEnd]);
                $activeSubscription->refresh();
            }
            //tính số ngày còn lại
            if ($activeSubscription->end_date) {
                $daysRemaining = max(0, now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSubscription->end_date)->startOfDay(), false));
            }
        }
        // Check xem chủ trọ này đã từng sử dụng gói dùng thử VIP chưa
        $hasUsedTrial = LandlordSubscription::where('user_id', $user->id)
            ->whereHas('plan', function ($q) {
                $q->where('badge', 'LIKE', '%DÙNG THỬ%')
                    ->orWhere('name', 'LIKE', '%Dùng Thử%');
            })->exists();

        // Truy vấn danh sách dịch vụ có sẵn
        $plansQuery = SubscriptionPlan::with('features')
            ->where('is_active', true);

        // Nếu đã sử dụng gói dùng thử -> Ẩn gói dùng thử VIP khỏi danh sách mua
        if ($hasUsedTrial) {
            $plansQuery->where(function ($q) {
                $q->where('price', '>', 0)
                    ->orWhere('badge', 'CƠ BẢN')
                    ->orWhere('name', 'LIKE', '%Cơ Bản%');
            });
        }
        $plans = $plansQuery->orderBy('sort_order', 'asc')->get();
        // Lịch sử đăng ký gói
        $history = LandlordSubscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
        // Đơn pending gần nhất (nếu đang chờ thanh toán)
        $pendingSubscription = LandlordSubscription::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        // Thông tin ngân hàng của Admin
        $settings = \App\Models\Setting::pluck('value', 'key');
        $adminBank = [
            'bank_name' => $settings['admin_bank_name'] ?? '',
            'bank_code' => $settings['admin_bank_code'] ?? '',
            'account_no' => $settings['admin_account_no'] ?? '',
            'account_name' => $settings['admin_account_name'] ?? '',
        ];
        return Inertia::render('Landlord/Subscriptions/Index', [
            'activeSubscription' => $activeSubscription,
            'daysRemaining' => $daysRemaining,
            'plans' => $plans,
            'history' => $history,
            'pendingSubscription' => $pendingSubscription,
            'adminBank' => $adminBank,
        ]);
    }

    public function purchase(PurchaseSubscriptionRequest $request)
    {
        $validated = $request->validated();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $user = auth()->user();
        $activeSub = $user->activeSubscription()->with('plan')->first();
        // Ràng buộc chặn mua thêm nếu đang có đơn chờ duyệt
        $existingPending = LandlordSubscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();
        // Ràng buộc: không cho phép hạ gói xuống giá thấp hơn khi gói cũ chưa hết hạn
        if ($activeSub && $activeSub->plan) {
            // Nếu giá gói mới nhỏ hơn giá gói đang dùng
            if ((float) $plan->price < (float) $activeSub->plan->price) {
                return redirect()->back()->with('error', "Gói hiện tại của bạn là \"{$activeSub->plan->name}\". Bạn không thể hạ xuống gói có giá thấp hơn khi gói cũ chưa hết hạn!");
            }
        }
        if ($existingPending) {
            return redirect()->back()->with('error', 'Bạn đang có một đơn mua gói chưa hoàn tất hoặc đang chờ Admin duyệt. Vui lòng chờ xử lý trước khi đăng ký gói mới!');
        }
        // Ràng buộc chặn mua lại chính gói đang sử dụng (trừ gói gia hạn)
        $activeSub = $user->activeSubscription;
        if ($activeSub && $activeSub->plan_id == $plan->id && $plan->duration_days != -1) {
            return redirect()->back()->with('error', 'Bạn hiện đang sử dụng gói dịch vụ này rồi');
        }
        $service = app(SubscriptionService::class);
        // Nếu là gói 0đ (free trial)
        if ($plan->price == 0) {
            $sub = $service->createPendingSubscription($user, $plan);
            $service->activateSubscription($sub);
            return redirect()->back()->with('success', 'Kích hoạt gói thành công!');
        }
        // GHI LOG
        $subscription = $service->createPendingSubscription($user, $plan);
        AuditLogger::log('Đăng ký mua gói dịch vụ', "Chủ trọ \"{$user->name}\" vừa khởi tạo đơn đăng ký mua gói {$plan->name} (Mã GD: {$subscription->payment_code})");
        return redirect()->back()->with('success', 'Đã khởi tạo đơn mua gói. Vui lòng quét mã VietQr để thanh toán!');
    }

    // Phần upload bằng chứng mua gói của chủ trọ
    public function uploadProof(UploadProofRequest $request, $id)
    {
        $disk = (config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret')) ? 'r2_public' : 'public';
        $subscription = LandlordSubscription::where('user_id', auth()->id())
            ->findOrFail($id);
        if ($request->hasFile('proof_image')) {
            $path = $request->file('proof_image')->store('subscription_proofs', $disk);
            $subscription->update([
                'proof_image' => '/storage/' . $path,
            ]);
            // Ghi log
            AuditLogger::log("Tải bill chuyển khoản gói", "Chủ trọ \"{$subscription->user->name}\" vừa tải ảnh bill cho gói {$subscription->plan->name} (Mã GD: {$subscription->payment_code})");
            // Gửi thông báo cho tất cả tài khoản admin
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new SubscriptionNotification(
                    "Đơn Mua Gói Mới Cần Duyệt",
                    "Chủ trọ \"{$subscription->user->name}\" vừa tải bill cho gói {$subscription->plan->name} (Mã: {$subscription->payment_code}).",
                    route('admin.landlord-subscriptions.index', ['status' => 'pending']),
                    'info'
                ));
            }
        }
        return redirect()->back()->with('success', 'Đã tải ảnh hoá đơn chuyển khoản thành công! Admin sẽ kiểm tra và duyệt sớm nhất.');
    }

    // Phần kiểm tra trạng thái
    public function checkStatus($id)
    {
        $subscription = LandlordSubscription::where('user_id', auth()->id())->find($id);
        if (!$subscription) {
            return response()->json(['success' => false], 404);
        }
        return response()->json([
            'success' => true,
            'status' => $subscription->status,
        ]);
    }

    // Hiển thị danh sách lịch sử mua gói dịch vụ của chủ trọ
    public function history()
    {
        $user = auth()->user();
        // Lấy danh sách lịch sử mua gói
        $history = LandlordSubscription::where('user_id', $user->id)
            ->with('plan')
            ->orderBy('id', 'desc')
            ->paginate(10);
        return Inertia::render('Landlord/Subscriptions/History', [
            'history' => $history,
        ]);
    }
    //huỷ đơn mua gói đang chờ thanh toán
    public function cancel($id)
    {
        $subscription = LandlordSubscription::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);
        $subscription->update([
            'status' => 'rejected',
            'admin_note' => 'Chủ trọ đã chủ động huỷ đơn thanh toán.',
        ]);
        AuditLogger::log('Hủy đơn mua gói dịch vụ', "Chủ trọ \"" . auth()->user()->name . "\" vừa hủy đơn đăng ký mua gói {$subscription->plan->name} (Mã GD: {$subscription->payment_code})");
        return redirect()->back()->with('success', 'Đã huỷ đơn thanh toán gói dịch vụ thành công!');
    }
}
