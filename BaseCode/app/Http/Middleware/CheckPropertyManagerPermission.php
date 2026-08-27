<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BoardingHouse;
use App\Models\PropertyManager;
use Symfony\Component\HttpFoundation\Response;

class CheckPropertyManagerPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Vui lòng đăng nhập.'], 401)
                : redirect()->route('login');
        }

        // 1. Tìm ID cơ sở trọ từ Session hoặc từ Request
        $selectedHouseId = session('selected_boarding_house_id');

        if (!$selectedHouseId) {
            if ($request->filled('boarding_house_id')) {
                $selectedHouseId = $request->input('boarding_house_id');
            } elseif ($request->filled('room_id') || $request->route('id') || $request->route('roomId')) {
                $roomId = $request->input('room_id') ?? $request->route('id') ?? $request->route('roomId');
                $room = Room::find($roomId);
                if ($room) {
                    $selectedHouseId = $room->boarding_house_id;
                }
            } elseif ($request->filled('appointment_id')) {
                $apt = Appointment::with('room')->find($request->input('appointment_id'));
                if ($apt && $apt->room) {
                    $selectedHouseId = $apt->room->boarding_house_id;
                }
            }

            // Nếu vẫn chưa tìm thấy, lấy cơ sở trọ đầu tiên của chủ trọ
            if (!$selectedHouseId && $user->role === 'landlord') {
                $bh = BoardingHouse::where('user_id', $user->id)->first();
                if ($bh) {
                    $selectedHouseId = $bh->id;
                }
            }

            if ($selectedHouseId) {
                session(['selected_boarding_house_id' => $selectedHouseId]);
            }
        }

        if (!$selectedHouseId) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Vui lòng chọn cơ sở trọ trước khi thực hiện.'], 400)
                : redirect()->route('landlord.boarding-houses.index')->with('error', 'Vui lòng chọn một cơ sở trọ để làm việc.');
        }

        $boardingHouse = BoardingHouse::find($selectedHouseId);
        if (!$boardingHouse) {
            // Nếu session lưu ID cũ không khả thi, giải phóng và tìm từ dữ liệu request
            session()->forget('selected_boarding_house_id');

            $roomId = $request->input('room_id') ?? $request->route('id') ?? $request->route('roomId');
            if ($roomId) {
                $room = Room::find($roomId);
                if ($room) {
                    $boardingHouse = $room->boardingHouse;
                }
            } elseif ($request->filled('appointment_id')) {
                $apt = Appointment::with('room.boardingHouse')->find($request->input('appointment_id'));
                if ($apt && $apt->room) {
                    $boardingHouse = $apt->room->boardingHouse;
                }
            }

            if (!$boardingHouse && $user->role === 'landlord') {
                $boardingHouse = BoardingHouse::where('user_id', $user->id)->first();
            }

            if (!$boardingHouse) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Cơ sở trọ không tồn tại.'], 404)
                    : redirect()->route('landlord.boarding-houses.index')->with('error', 'Cơ sở trọ không tồn tại.');
            }

            session(['selected_boarding_house_id' => $boardingHouse->id]);
        }

        // 2. Nếu là chủ trọ chính -> Cho phép làm mọi việc
        if ($boardingHouse->user_id === $user->id) {
            return $next($request);
        }

        // 3. Nếu là tài khoản quản lý phụ -> Kiểm tra quyền thực tế
        $manager = PropertyManager::where('boarding_house_id', $boardingHouse->id)
            ->where('user_id', $user->id)
            ->first();

        if ($manager && is_array($manager->permissions) && in_array($permission, $manager->permissions)) {
            return $next($request);
        }

        // Bị chặn quyền truy cập nếu không có quyền
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Bạn không được phân quyền thực hiện chức năng này.'], 403);
        }

        return redirect()->route('landlord.dashboard')->with('error', 'Tài khoản của bạn không được phân quyền quản lý chức năng này.');
    }
}
