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
        //lấy ID cơ sở trọ đang làm việc từ Session
        $selectedHouseId = session('selected_boarding_house_id');
        if (!$selectedHouseId) {
            return $request->expectsJson() ? response()->json(['message' => 'Vui lòng chọn cơ sở trước khi thực hiện.'], 400) : redirect()->route('landlord.boarding-houses.index')->with('error', 'Vui lòng chọn một cơ sở trọ để làm việc.');
        }
        $boardingHouse = BoardingHouse::find($selectedHouseId);
        if (!$boardingHouse) {
            abort(404, 'Cơ sở trọ không tồn tại.');
        }
        //nếu là chủ trọ chính->cho phép làm mọi việc
        if ($boardingHouse->user_id === $user->id) {
            return $next($request);
        }
        //nếu là tài khoản quản lý phụ -> kiểm tra quyền thực tế
        $manager = PropertyManager::where('boarding_house_id', $selectedHouseId)
            ->where('user_id', $user->id)
            ->first();
        if ($manager && in_array($permission, $manager->permissions)) {
            return $next($request);
        }
        //bị chặn quyền truy cập nếu không có quyền
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Bạn không được phân quyền thực hiện chức năng này.'], 403);
        }
        return redirect()->route('landlord.dashboard')->with('error', 'Tài khoản của bạn không được phân quyền quản lý chức năng này.');
    }
}
