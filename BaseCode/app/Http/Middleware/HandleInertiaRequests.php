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
        $selectedBoardingHouseId = null;

        if ($user && $user->role === 'landlord') {
            $boardingHouses = \App\Models\BoardingHouse::where('user_id', $user->id)
                ->where('status', 'approved')
                ->get(['id', 'name']);
            
            $selectedBoardingHouseId = session('selected_boarding_house_id');
            if (!$selectedBoardingHouseId && $boardingHouses->count() > 0) {
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
                    ? \App\Models\Appointment::where('landlord_id', $user->id)->where('status', 'pending')->count() : 0,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
