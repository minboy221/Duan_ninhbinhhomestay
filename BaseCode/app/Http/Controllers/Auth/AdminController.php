<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; // Để thống kê dữ liệu nếu cần

class AdminController extends Controller
{
    public function index()
    {
        // Bạn có thể lấy dữ liệu thống kê để hiển thị ở Dashboard admin
        $totalUsers = User::where('role', 0)->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                // Thêm các thông số khác cho đồ án của bạn tại đây
            ]
        ]);
    }

    // Các hàm quản lý khác (ví dụ: quản lý danh sách người dùng)
    public function users()
    {
        $users = User::all();
        return Inertia::render('Admin/Users/index', [
            'users' => $users
        ]);
    }
}