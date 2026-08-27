<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LandlordSubscriptionRejectRequest;
use App\Models\LandlordSubscription;
use App\Services\AuditLogger;
use App\Services\SubscriptionService;
use App\Notifications\SubscriptionNotification;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class LandlordSubscriptionController extends Controller
{
    // Phần hiển thị danh sách
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $query = LandlordSubscription::with(['user', 'plan', 'approver'])
            ->latest();
        $query->where(function ($q) {
            $q->where('status', '!=', 'pending')
                ->orWhereNotNull('proof_image');
        });
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $subscriptions = $query->paginate(15)->withQueryString();
        return Inertia::render('Admin/Subscriptions/Landlords/Index', [
            'subscriptions' => $subscriptions,
            'currentStatus' => $status,
        ]);
    }

    // Phần chấp thuận dịch vụ
    public function approve(Request $request, $id)
    {
        $subscriptions = LandlordSubscription::findOrFail($id);
        if ($subscriptions->status === 'active') {
            return redirect()->back()->with('error', 'Đơn mua gói này đã được kích hoạt trước đó.');
        }
        $subscriptionsService = app(SubscriptionService::class);
        $subscriptionsService->activateSubscription($subscriptions, auth()->user());
        // Gửi thông báo cho chủ trọ
        $endDate = $subscriptions->end_date ? $subscriptions->end_date->format('d/m/Y') : 'Vĩnh Viễn';
        $subscriptions->user->notify(new SubscriptionNotification(
            'Gói dịch vụ đã được kích hoạt!',
            "Gói \"{$subscriptions->plan->name}\" của bạn đã được Admin duyệt thành công. Hạn dùng đến ngày: {$endDate}.",
            route('landlord.subscriptions.index'),
            'success'
        ));
        // Ghi log
        AuditLogger::log('Duyệt đơn mua gói dịch vụ', "Admin vừa duyệt và kích hoạt gói \"{$subscriptions->plan->name}\" cho chủ trọ \"{$subscriptions->user->name}\" (Mã GD: {$subscriptions->payment_code})", true);
        return redirect()->back()->with('success', 'Đã duyệt và kích hoạt gói thành công cho chủ trọ!');
    }

    // Phần huỷ gói dịch vụ
    public function reject(LandlordSubscriptionRejectRequest $request, $id)
    {
        $subscription = LandlordSubscription::findOrFail($id);
        $reason = $request->validated()['admin_note'];
        $subscription->update([
            'status' => 'rejected',
            'admin_note' => $reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        // Ghi log
        AuditLogger::log('Từ chối đơn mua gói dịch vụ', "Admin vừa từ chối đơn mua gói \"{$subscription->plan->name}\" của chủ trọ \"{$subscription->user->name}\". Lý do: {$reason} (Mã GD: {$subscription->payment_code})", true);
        // Gửi thông báo từ chối cho chủ trọ
        $subscription->user->notify(new SubscriptionNotification(
            "Đơn mua gói bị từ chối",
            "Đơn mua gói \"{$subscription->plan->name}\" bị từ chối. Lý do: {$reason}.",
            route('landlord.subscriptions.history'),
            'error'
        ));
        return redirect()->back()->with('success', 'Đã từ chối đơn mua gói.');
    }
}
