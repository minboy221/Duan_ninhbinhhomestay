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
    //phần hiển thị danh sách các gói cho chủ trọ
    public function index()
    {
        $user = auth()->user();
        //tự động cập dùng thử 60 ngày nếu chủ trọ mới chưa có gói
        $service = app(SubscriptionService::class);
        $service->assignFreeTrial($user);
        //tự động check nếu hết hạn gói -> tự động kích hoạt gói miễn phí
        $service->ensureActiveSubscription($user);
        //lấy gói active hiện tại
        $activeSubscription = $user->activeSubscription()->with('plan.features')->first();
        //tính số ngày còn lại
        $daysRemaining = 0;
        if ($activeSubscription && $activeSubscription->end_date) {
            $daysRemaining = max(0, now()->startOfDay()->diffInDays($activeSubscription->end_date->startOfDay(), false));
        }
        //lấy danh sách các gói dịch vụ có sẵn
        $plans = SubscriptionPlan::with('features')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();
        //lịch sử đăng ký gói
        $history = LandlordSubscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
        //đơn pending gần nhất(nếu đang chờ thanh toán)
        $pendingSubscription = LandlordSubscription::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        //thông tin ngân hàng của Admin
        $settings = \App\Models\Setting::pluck('value', 'key');
        $adminBank = [
            'bank_name' => $settings['admin_bank_name'] ?? '',
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
        // ràng buộc chặn mua thêm nếu đang có đơn chờ duyệt
        $existingPending = LandlordSubscription::where('user_id', $user->id)
            ->where('status', 'penging')
            ->first();
        // ràng buộc: không cho phép hạ gói xuống giá thấp hơn khi gói cũ chưa hết hạn
        if ($activeSub && $activeSub->plan) {
            //nếu giá gói mới nhỏ hơn giá gói đang dùng
            if ((float) $plan->price < (float) $activeSub->plan->price) {
                return redirect()->back()->with('error', "Gói hiện tại của bạn là  \"{$activeSub->plan->name}\". Bạn không thể hạ xuống gói có giá thập hơn khi gói cũ chưa hết hạn!");
            }
        }
        if ($existingPending) {
            return redirect()->back()->with('error', 'Bạn đang có một đơn mua gói chưa hoàn tất hoặc đang chờ Admin duyệt. Vui lòng chờ xử lý trước khi đăng ký gói mới!');
        }
        // ràng buộc chặn mua lại chíng gói đang sử dụng (trừ gói gia hạn)
        $activeSub = $user->activeSubscription;
        if ($activeSub && $activeSub->plan_id == $plan->id && $plan->duration_days != -1) {
            return redirect()->back()->with('error', 'Bạn hiện đang sử dụng gói dịch vụ này rồi');
        }
        $service = app(SubscriptionService::class);
        //nếu là gói 0đ(free trial)
        if ($plan->price == 0) {
            $sub = $service->createPendingSubscription($user, $plan);
            $service->activateSubscription($sub);
            return redirect()->back()->with('success', 'Kích hoạt gói thành công!');
        }
        //GHI LOG
        $subscription = $service->createPendingSubscription($user, $plan);
        AuditLogger::log('Đăng ký mua gói dịch vụ', "Chủ trọ \"{$user->name}\" vừa khởi tạo đơn đăng ký mua gói {$plan->name} (Mã GD: {$subscription->payment_code})");
        return redirect()->back()->with('success', 'Đã khởi tạo đơn mua gói. Vui lòng quét mã VietQr để thanh toán!');
    }

    //phần upload bằng chứng mua gói của chủ trọ
    public function uploadProof(UploadProofRequest $request, $id)
    {
        $subscription = LandlordSubscription::where('user_id', auth()->id())
            ->findOrFail($id);
        if ($request->hasFile('proof_image')) {
            $path = $request->file('proof_image')->store('subscription_proofs', 'public');
            $subscription->update([
                'proof_image' => '/storage/' . $path,
            ]);
            //ghi log
            AuditLogger::log("Tải bill chuyển khoản gói", "Chủ trọ \"{$subscription->user->name}\" vừa tải ảnh bill cho gói {$subscription->plan->name} (Mã GD: {$subscription->payment_code})");
            //gửi thông báo cho tất cả tài khoản admin
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

    //phần kiểm tra trạng thái
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

    //hiển thị danh sách lịch sử mua gói dịch vụ của chủ trọ
    public function history()
    {
        $user = auth()->user();
        //lấy dang sách lịch sử mua gói
        $history = LandlordSubscription::where('user_id', $user->id)
            ->with('plan')
            ->orderBy('id', 'desc')
            ->paginate(10);
        return Inertia::render('Landlord/Subscriptions/History', [
            'history' => $history,
        ]);
    }
}
