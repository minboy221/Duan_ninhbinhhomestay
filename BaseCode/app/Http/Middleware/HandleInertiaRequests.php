<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        $manifestPath = public_path('build/manifest.json');
        if (!file_exists($manifestPath)) {
            $manifestPath = base_path('../public_html/build/manifest.json');
        }
        if (!file_exists($manifestPath)) {
            $manifestPath = base_path('public/build/manifest.json');
        }
        if (file_exists($manifestPath)) {
            return md5_file($manifestPath);
        }
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            try {
                // Check for today's approved appointments to notify
                $today = \Carbon\Carbon::today()->format('Y-m-d');
                $todayAppointments = \App\Models\Appointment::where('user_id', $user->id)
                    ->where('date', $today)
                    ->where('status', 'approved')
                    ->where('notified', false)
                    ->get();

                foreach ($todayAppointments as $apt) {
                    $user->notify(new \App\Notifications\AppointmentReminder($apt));
                    $apt->update(['notified' => true]);
                }
            } catch (\Throwable $e) {
                // Safely handle notification errors
            }
        }

        $boardingHouses = [];
        $selectedBoardingHouseId = session('selected_boarding_house_id');
        $isOwner = false;
        $managerPermissions = [];

        if ($user && $user->role === 'landlord') {
            //lấy danh sách ID cơ sở do chủ trọ sở hữu
            $ownerHouseIds = \App\Models\BoardingHouse::where('user_id', $user->id)->where('status', 'approved')
                ->pluck('id')
                ->toArray();
            //lấy danh sách Id cơ sở chủ trọ được làm quản lý phụ
            $managedHouseIds = \App\Models\PropertyManager::where('user_id', $user->id)
                ->pluck('boarding_house_id')
                ->toArray();
            //gộp tất cả các id
            $allHouseIds = array_unique(array_merge($ownerHouseIds, $managedHouseIds));
            //lấy thông tin cơ sở
            $boardingHouses = \App\Models\BoardingHouse::whereIn('id', $allHouseIds)
                ->where('status', 'approved')
                ->get(['id', 'name', 'address_detail', 'district', 'latitude', 'longitude']);
            //tự động chọn cơ sở đầu tiên trong session chưa lưu cơ sở nào
            if (!$selectedBoardingHouseId && $boardingHouses->isNotEmpty()) {
                $selectedBoardingHouseId = $boardingHouses->first()->id;
                session(['selected_boarding_house_id' => $selectedBoardingHouseId]);
            }
            //xác định quyền trên cơ sở đang chọn
            if ($selectedBoardingHouseId) {
                //check user có phải chủ sở hữu chính của cơ sở
                $currentHouse = \App\Models\BoardingHouse::find($selectedBoardingHouseId);
                if ($currentHouse && $currentHouse->user_id === $user->id) {
                    $isOwner = true;
                    $managerPermissions = ['*']; //toàn quyền
                } else {
                    //nếu là tài khoản phụ -> lấy mảng permissions từ bảng PropertyManager
                    $manager = \App\Models\PropertyManager::where('boarding_house_id', $selectedBoardingHouseId)
                    ->where('user_id', $user->idi)
                    ->first();
                    if($manager){
                        $perms = $manager->permissions;
                        $managerPermissions = is_array($perms) ? $perms : (is_string($perms) ? json_decode($perms, true) ?: explode(',', $perms) : []);
                    }else{
                        $managerPermissions = [];
                    }
                }
            }
        }

        $userData = null;
        if ($user) {
            $user->load('verification');
            $activeSub = $user->activeSubscription;
            $hasVipFrame = false;

            if ($activeSub && $activeSub->plan) {
                $frameFeat = $activeSub->plan->features->where('feature_code', 'avatar_frame')->first();
                $hasVipFrame = $frameFeat && in_array($frameFeat->pivot->feature_value, ['gold', 'true', '1']);
            }

            $userData = array_merge($user->toArray(), [
                'has_vip_frame' => $hasVipFrame,
                'features' => [
                    'manage_invoices'  => $user->hasFeature('manage_invoices'),
                    'manage_contracts' => $user->hasFeature('manage_contracts'),
                    'manage_roommates' => $user->hasFeature('manage_roommates'),
                    'manage_reports'   => $user->hasFeature('manage_reports'),
                    'manage_managers'  => $user->hasFeature('manage_managers'),
                ],
            ]);
        }

        $notifications = [];
        if ($user) {
            try {
                $notifications = $user->unreadNotifications;
            } catch (\Throwable $e) {
                $notifications = [];
            }
        }

        $hasActiveContract = false;
        if ($user) {
            try {
                $hasActiveContract = \App\Models\Contract::where('tenant_id', $user->id)
                    ->whereIn('status', ['active', 'signed', 'expiring', 'awaiting_upload'])
                    ->exists()
                    || \App\Models\RoomResident::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->exists();
            } catch (\Throwable $e) {
                $hasActiveContract = false;
            }
        }

        $settings = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::pluck('value', 'key')->map(function ($val) {
                    $decoded = json_decode($val, true);
                    return is_array($decoded) ? $decoded : $val;
                });
            }
        } catch (\Throwable $e) {
            $settings = [];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $userData,
                'boarding_houses' => $boardingHouses,
                'selected_boarding_house_id' => $selectedBoardingHouseId,
                'is_owner' => $isOwner,
                'permissions' => $managerPermissions,
                'features' => $request->user() ? [
                    'manage_invoices' => $request->user()->hasFeature('manage_invoices'),
                    'manage_contracts' => $request->user()->hasFeature('manage_contracts'),
                    'manage_roommates' => $request->user()->hasFeature('manage_roommates'),
                    'manage_reports' => $request->user()->hasFeature('manage_reports'),
                    'manage_managers' => $request->user()->hasFeature('manage_managers'),
                ] : [],
                'has_submitted_verification' => $user
                    ? \Illuminate\Support\Facades\DB::table('user_verifications')->where('user_id', $user->id)->exists() : false,
                'has_active_contract' => $hasActiveContract,
                'notifications' => $notifications,
                'pending_appointments_count' => $user && $user->role === 'landlord'
                    ? \App\Models\Appointment::where('landlord_id', $user->id)
                        ->where('status', 'pending')
                        ->whereHas('room', function ($q) use ($selectedBoardingHouseId) {
                            $q->where('boarding_house_id', $selectedBoardingHouseId);
                        })->count() : 0,
                'pending_landlord_reports_count' => 0,
                'admin_counts' => $user && $user->role === 'admin' ? [
                    'reports' => \App\Models\Report::where('status', 'pending')->count(),
                    'verifications' => \App\Models\UserVerification::where('kyc_status', 'pending')->count(),
                    'room_posts' => \App\Models\RoomPost::where('status', 'pending')->count(),
                    'boarding_houses' => \App\Models\BoardingHouse::where('status', 'pending')->count(),
                    'latest_audit_log_id' => \App\Models\AuditLog::max('id') ?? 0,
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'settings' => $settings,
        ]);
    }
}
