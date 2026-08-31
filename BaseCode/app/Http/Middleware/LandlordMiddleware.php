<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\LandlordSubscriptionController;

class LandlordMiddleware
{
    /**
     * Handle an incoming request.
     * Chỉ cho phép người dùng có vai trò là 'landlord' (chủ trọ) truy cập.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Nếu chưa đăng nhập hoặc vai trò không phải landlord -> Đẩy về trang chủ kèm thông báo
        if (!$user || $user->role !== 'landlord') {
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập khu vực quản lý.');
        }

        // 2. Kiểm tra nếu người dùng mang vai trò landlord nhưng KHÔNG sở hữu nhà trọ nào VÀ KHÔNG quản lý nhà trọ nào
        $ownsHouse = \App\Models\BoardingHouse::where('user_id', $user->id)->exists();
        $managesHouse = \App\Models\PropertyManager::where('user_id', $user->id)->exists();

        if (!$ownsHouse && !$managesHouse) {
            // Tự động thu hồi vai trò về tenant
            $user->role = 'tenant';
            $user->save();
            \Illuminate\Support\Facades\Auth::setUser($user->fresh());

            return redirect()->route('home')->with('error', 'Quyền đồng quản lý của bạn đã bị chủ trọ hủy bỏ.');
        }

        return $next($request);
    }
}