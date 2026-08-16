<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LandlordSubscriptionRejectRequest;
use App\Models\LandlordSubscription;
use App\Services\SubscriptionService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class LandlordSubscriptionController extends Controller
{
    //phần hiên thị danh sách
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $query = LandlordSubscription::with(['user', 'plan', 'approver'])
            ->latest();
        $query->where(function ($q){
            $q->where('status', '!=','pending')
            ->orWhereNotNull('proof_image');
        });
        if($status !== 'all'){
            $query->where('status', $status);
        }
        $subscriptions = $query->paginate(15)->withQueryString();
        return Inertia::render('Admin/Subscriptions/Landlords/Index',[
            'subscriptions' => $subscriptions,
            'currentStatus' => $status,
        ]);
    }

    //phần chấp thuận dịch vụ
    public function approve(Request $request, $id){
        $subscriptions = LandlordSubscription::findOrFail($id);
        if($subscriptions->status === 'active'){
            return redirect()->back()->with('error','Đơn mua gói này đã được kịch hoạt trước đó.');
        }
        $subscriptionsService = app(SubscriptionService::class);
        $subscriptionsService->activateSubscription($subscriptions, auth()->user());
        return redirect()->back()->with('success','Đã duyệt và kích hoạt gói thanh công cho chủ trọ!');
    }

    //phần huỷ gói dịch vụ
    public function reject(LandlordSubscriptionRejectRequest $request, $id){
        $validated = $request->validated();
        $subscription = LandlordSubscription::findOrFail($id);
        $subscription->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return redirect()->back()->with('success','Đã từ chối đơn mua gói dịch vụ.');
    }
}
