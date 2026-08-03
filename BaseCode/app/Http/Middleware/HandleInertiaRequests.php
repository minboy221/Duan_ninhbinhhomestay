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
    public function version(Request $request): string|null
    {
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
        }

        $boardingHouses = [];
        $selectedBoardingHouseId = session('selected_boarding_house_id');

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
                ->get(['id', 'name', 'address_detail', 'district', 'latitude','longitude']);
            //tự động chọn cơ sở đầu tiên trong session chưa lưu cơ sở nào
            if (!$selectedBoardingHouseId && $boardingHouses->isNotEmpty()) {
                $selectedBoardingHouseId = $boardingHouses->first()->id;
                session(['selected_boarding_house_id' => $selectedBoardingHouseId]);
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? $user->load('verification') : null,
                'boarding_houses' => $boardingHouses,
                'selected_boarding_house_id' => $selectedBoardingHouseId,
                'has_submitted_verification' => $user
                    ? \Illuminate\Support\Facades\DB::table('user_verifications')->where('user_id', $user->id)->exists() : false,
                'notifications' => $user ? $user->unreadNotifications : [],
                'pending_appointments_count' => $user && $user->role === 'landlord'
                    ? \App\Models\Appointment::where('landlord_id', $user->id)
                        ->where('status', 'pending')
                        ->whereHas('room', function ($q) use ($selectedBoardingHouseId) {
                            $q->where('boarding_house_id', $selectedBoardingHouseId);
                        })->count() : 0,
                'pending_landlord_reports_count' => $user && $user->role === 'landlord'
                    ? \App\Models\Report::where('status', 'pending')
                        ->whereHasMorph('reportable', [
                            \App\Models\Room::class,
                            \App\Models\Invoice::class,
                            \App\Models\Contract::class,
                            \App\Models\BoardingHouse::class
                        ], function ($query, $type) use ($user, $selectedBoardingHouseId) {
                            if ($type === \App\Models\Room::class) {
                                $query->whereHas('boardingHouse', function ($q) use ($user, $selectedBoardingHouseId) {
                                    $q->where('user_id', $user->id)
                                      ->where('id', $selectedBoardingHouseId);
                                });
                            } elseif ($type === \App\Models\Invoice::class) {
                                $query->whereHas('contract.room.boardingHouse', function ($q) use ($user, $selectedBoardingHouseId) {
                                    $q->where('user_id', $user->id)
                                      ->where('id', $selectedBoardingHouseId);
                                });
                            } elseif ($type === \App\Models\Contract::class) {
                                $query->whereHas('room.boardingHouse', function ($q) use ($user, $selectedBoardingHouseId) {
                                    $q->where('user_id', $user->id)
                                      ->where('id', $selectedBoardingHouseId);
                                });
                            } elseif ($type === \App\Models\BoardingHouse::class) {
                                $query->where('user_id', $user->id)
                                      ->where('id', $selectedBoardingHouseId);
                            }
                        })->count() : 0,
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
            'settings' => \App\Models\Setting::pluck('value', 'key')->map(function ($val) {
                $decoded = json_decode($val, true);
                return is_array($decoded) ? $decoded : $val;
            }),
        ]);
    }
}
