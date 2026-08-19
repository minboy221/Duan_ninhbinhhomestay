<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionPlanController extends Controller
{
    //phần hiển thị danh sách của gói đăng ký
    public function index()
    {
        $plans = SubscriptionPlan::with('features')->orderBy('sort_order', 'asc')->get();
        $orderCodes = [
            'max_rooms',
            'max_properties',
            'vip_badge',
            'priority_listing',
            'export_reports',
            'max_boarding_houses',
            'max_listings',
            'manage_invoices',
            'manage_contracts',
            'manage_roommates',
            'manage_reports',
            'manage_managers',
            'avatar_frame',
        ];
        $features = Feature::all()->sortBy(function ($item) use ($orderCodes) {
            $index = array_search($item->feature_code, $orderCodes);
            return $index !== false ? $index : 999;
        })->values();

        //đọc cấu hình ngân hàng admin
        $settings = \App\Models\Setting::pluck('value', 'key');
        $adminBank = [
            'bank_name' => $settings['admin_bank_name'] ?? '',
            'bank_code' => $settings['admin_bank_code'] ?? '',
            'account_no' => $settings['admin_account_no'] ?? '',
            'account_name' => $settings['admin_account_name'] ?? '',
        ];

        return Inertia::render('Admin/Subscriptions/Plans/Index', [
            'plans' => $plans,
            'features' => $features,
            'adminBank' => $adminBank,
        ]);
    }

    //phần cập nhật thông tin ngân hàng của admin
    public function updateBankSettings(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'bank_code' => 'nullable|string|max:20',
            'account_no' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
        ], [
            'bank_name.required' => 'Vui lòng chọn hoặc nhập tên Ngân hàng.',
            'account_no.required' => 'Vui lòng nhập Số tài khoản ngân hàng.',
            'account_name.required' => 'Vui lòng nhập Tên chủ tài khoản.',
        ]);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'admin_bank_name'],
            ['value' => $validated['bank_name']]
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'admin_bank_code'],
            ['value' => $validated['bank_code'] ?? '']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'admin_account_no'],
            ['value' => $validated['account_no']]
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => 'admin_account_name'],
            ['value' => $validated['account_name']]
        );

        return redirect()->back()->with('success', 'Đã cập nhật thông tin ngân hàng thành công!');
    }
    //phần tạo mới gói đăng ký
    public function store(SubscriptionPlanRequest $request)
    {
        $validated = $request->validated();
        $plan = SubscriptionPlan::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'badge' => $validated['badge'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);
        if (!empty($validated['features'])) {
            $syncData = [];
            foreach ($validated['features'] as $featureId => $value) {
                if ($value !== null && $value !== '') {
                    $strVal = ($value === false || $value === 'false') ? 'false' : (($value === true || $value === 'true') ? 'true' : (string) $value);
                    $syncData[$featureId] = ['feature_value' => $strVal];
                }
            }
            $plan->features()->sync($syncData);
        }
        return redirect()->back()->with('success', 'Thêm mới gói dịch vụ thành công!');
    }

    //phần chỉnh sửa
    public function update(SubscriptionPlanRequest $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $validated = $request->validated();
        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'badge' => $validated['badge'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);
        if (isset($validated['features'])) {
            $syncData = [];
            foreach ($validated['features'] as $featureId => $value) {
                if ($value !== null && $value !== '') {
                    $strVal = ($value === false || $value === 'false') ? 'false' : (($value === true || $value === 'true') ? 'true' : (string) $value);
                    $syncData[$featureId] = ['feature_value' => $strVal];
                }
            }
            $plan->features()->sync($syncData);
        }
        return redirect()->back()->with('success', 'Cập nhật gói dịch vụ thành công!');
    }

    //phần xoá gói
    public function destroy($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        //check xem có chủ trọ đang dùng hoặc đang chờ duyệt gói này 
        $activeSubCount = \App\Models\LandlordSubscription::where('plan_id',$plan->id)
        ->whereIn('status',['active','pending'])
        ->count();
        //nếu có chủ trọ đang dùng -> chặn xoá và báo lỗi chi tiết
        if($activeSubCount > 0){
            return redirect()->back()->with('error',"Không thể xoá gói \"{$plan->name}\" vì đang có {$activeSubCount} chủ trọ đang sử dụng hoặc chờ duyệt. Bạn chỉ có thể Tắt Kích Hoạt gói này để ẩn khỏi người dùng mới!");
        }
        $plan->delete();
        return redirect()->back()->with('success', 'Xoá gói dịch vụ thành công!');
    }
}
