<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LandlordMiddleware
{
    /**
     * Handle an incoming request.
     * Chỉ cho phép người dùng có vai trò là 'landlord' (chủ trọ) truy cập.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra nếu chưa đăng nhập (Giao diện web thường đã qua middleware auth trước đó)
        // 2. Hoặc nếu đã đăng nhập nhưng cột 'role' trong bảng users khác 'landlord'
        if (!auth()->check() || auth()->user()->role !== 'landlord') {

            // Trả về trang lỗi 403 chuẩn HTTP kèm thông báo chặn quyền
            abort(403, 'Bạn không có quyền truy cập khu vực này.');
        }

        // Nếu hợp lệ, cho phép request tiếp tục đi tiếp vào Controller xử lý nghiệp vụ
        return $next($request);
    }
}