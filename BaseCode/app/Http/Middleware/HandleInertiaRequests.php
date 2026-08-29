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
            $userId = $user->id;

            // Lấy tất cả cơ sở trọ (Sở hữu chính + Được phân quyền phụ) trong 1 QUERY duy nhất
            $boardingHouses = \App\Models\BoardingHouse::where('status', 'approved')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('managers', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->get(['id', 'name', 'address_detail', 'district', 'latitude', 'longitude', 'user_id']);
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
                        ->where('user_id', $user->id)
                        ->first();
                    if ($manager) {
                        $perms = $manager->permissions;
                        $managerPermissions = is_array($perms) ? $perms : (is_string($perms) ? json_decode($perms, true) ?: explode(',', $perms) : []);
                    } else {
                        $managerPermissions = [];
                    }
                }
            }
        }

        $userData = null;
        if ($user) {
            $user->load('verification');

            // 1. Xác định tài khoản mục tiêu (Nếu là Quản lý phụ -> Lấy Chủ Trọ Chính)
            $targetUser = $user;
            if ($selectedBoardingHouseId) {
                $currentHouse = \App\Models\BoardingHouse::find($selectedBoardingHouseId);
                if ($currentHouse && $currentHouse->user_id !== $user->id) {
                    $targetUser = $currentHouse->user;
                }
            }

            // 2. Đọc gói active kèm danh sách tính năng features
            $activeSub = $targetUser ? $targetUser->activeSubscription()->with('plan.features')->first() : null;

            $packageName = ($activeSub && $activeSub->plan)
                ? $activeSub->plan->name
                : ($targetUser->package_name ?? 'Gói Cơ Bản');

            // 3. Đọc tính năng đẩy tin 'priority_listing' do Admin cấu hình cho Gói này
            $bumpCredits = $targetUser ? ($targetUser->bump_credits ?? 0) : 0;
            if ($activeSub && $activeSub->plan) {
                $pFeature = $activeSub->plan->features->firstWhere('feature_code', 'priority_listing');
                $pVal = $pFeature ? (string) $pFeature->pivot->feature_value : '0';
                if ($pVal === '-1') {
                    $bumpCredits = 'Vô hạn';
                } elseif (is_numeric($pVal) && (int) $pVal > 0) {
                    // Nếu gói có số lượt cố định -> lấy số nhỏ hơn giữa DB và Gói
                    $bumpCredits = min((int) $targetUser->bump_credits, (int) $pVal);
                } else {
                    // Nếu gói không cấu hình lượt đẩy tin
                    $bumpCredits = 0;
                }
            } else {
                $bumpCredits = 0;
            }

            $hasVipFrame = false;
            if ($activeSub && $activeSub->plan) {
                $frameFeat = $activeSub->plan->features->firstWhere('feature_code', 'avatar_frame');
                $hasVipFrame = $frameFeat && in_array($frameFeat->pivot->feature_value, ['gold', 'true', '1']);
            }

            $subscriptionExpiring = false;
            $subscriptionDaysRemaining = null;
            if ($activeSub && $activeSub->end_date) {
                $daysLeft = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSub->end_date)->startOfDay(), false);
                if ($daysLeft >= 0 && $daysLeft <= 3) {
                    $subscriptionExpiring = true;
                    $subscriptionDaysRemaining = $daysLeft;

                    $alreadyNotified = $user->unreadNotifications()
                        ->where('type', 'App\Notifications\SubscriptionNotification')
                        ->where('data->title', 'Gói Dịch Vụ Sắp Hết Hạn')
                        ->exists();

                    if (!$alreadyNotified) {
                        $endDateStr = \Carbon\Carbon::parse($activeSub->end_date)->format('d/m/Y');
                        $user->notify(new \App\Notifications\SubscriptionNotification(
                            "Gói Dịch Vụ Sắp Hết Hạn",
                            "Gói \"{$packageName}\" của bạn sẽ hết hạn vào ngày {$endDateStr} (Còn {$daysLeft} ngày). Vui lòng gia hạn để không bị gián đoạn!",
                            route('landlord.subscriptions.index'),
                            'warning'
                        ));
                    }
                }
            }

            // 4. Truyền dữ liệu sang Vue Frontend
            $userData = array_merge($user->toArray(), [
                'package_name' => $packageName,
                'bump_credits' => $bumpCredits,
                'has_vip_frame' => $hasVipFrame,
                'subscription_expiring' => $subscriptionExpiring,
                'subscription_days_remaining' => $subscriptionDaysRemaining,
                'features' => [
                    'manage_invoices' => $user->hasFeature('manage_invoices'),
                    'manage_contracts' => $user->hasFeature('manage_contracts'),
                    'manage_roommates' => $user->hasFeature('manage_roommates'),
                    'manage_reports' => $user->hasFeature('manage_reports'),
                    'manage_managers' => $user->hasFeature('manage_managers'),
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

        $adminCounts = null;
        if ($user && $user->role === 'admin') {
            try {
                $adminCounts = [
                    'users' => \App\Models\User::whereNotNull('profile_unlock_reason')->count(),
                    'reports' => \App\Models\Report::where('status', 'pending')->count(),
                    'verifications' => \Illuminate\Support\Facades\DB::table('user_verifications')->where('status', 'pending')->count(),
                    'room_posts' => \App\Models\RoomPost::where('status', 'pending')->count(),
                    'boarding_houses' => \App\Models\BoardingHouse::where('status', 'pending')->count(),
                    'pending_subscriptions' => \App\Models\LandlordSubscription::where('status', 'pending')->whereNotNull('proof_image')->count(),
                    'contacts' => \Illuminate\Support\Facades\Schema::hasTable('contacts') ? \Illuminate\Support\Facades\DB::table('contacts')->where('status', 'pending')->count() : 0,
                    'reviews' => \Illuminate\Support\Facades\Schema::hasTable('reviews') ? \App\Models\Review::where('status', 'pending')->count() : 0,
                    'latest_audit_log_id' => \App\Models\AuditLog::max('id') ?? 0,
                ];
            } catch (\Throwable $e) {
                $adminCounts = [];
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $userData,
                'admin_counts' => $adminCounts,
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
                'pending_appointments_count' => ($user && $user->role === 'landlord' && $selectedBoardingHouseId)
                    ? \Illuminate\Support\Facades\Cache::remember("pending_apt_{$user->id}_{$selectedBoardingHouseId}", 30, function () use ($user, $selectedBoardingHouseId) {
                        return \App\Models\Appointment::where('landlord_id', $user->id)
                            ->where('status', 'pending')
                            ->whereHas('room', function ($q) use ($selectedBoardingHouseId) {
                                $q->where('boarding_house_id', $selectedBoardingHouseId);
                            })->count();
                    }) : 0,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'settings' => $settings,
        ]);
    }
}
