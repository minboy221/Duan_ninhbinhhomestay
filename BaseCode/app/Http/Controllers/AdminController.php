<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/dashboard', [
            'stats' => [
                'totalUsers'      => User::count(),
                'newUsersToday'   => User::whereDate('created_at', today())->count(),
                'pendingApproval' => 0,
                'reports'         => 0,
            ]
        ]);
    }

    public function users()
    {
        $users = User::where('role', '!=', 'admin')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/Users/index', [
            'users' => $users
        ]);
    }

    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'locked' : 'active';
        $user->save();

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái người dùng thành công.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Cấm xóa admin để tránh lỗi hệ thống (tùy chọn nhưng nên có)
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Không thể xóa Admin duy nhất của hệ thống.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Đã xóa người dùng thành công.');
    }

    public function landlords()
    {
        return Inertia::render('Admin/Landlords/index');
    }

    public function approval()
    {
        return Inertia::render('Admin/Approval/index');
    }

    public function categories()
    {
        return Inertia::render('Admin/Category/index');
    }

    public function reports()
    {
        return Inertia::render('Admin/Reports/index');
    }

    public function reviews()
    {
        return Inertia::render('Admin/Reviews/index');
    }

    public function revenue()
    {
        return Inertia::render('Admin/Revenue/index');
    }

    public function roles()
    {
        return Inertia::render('Admin/Roles/index');
    }

    public function auditlog()
    {
        return Inertia::render('Admin/AuditLog/index');
    }

    public function website()
    {
        return Inertia::render('Admin/WebEditor/index');
    }

    public function ads()
    {
        return Inertia::render('Admin/Ads/index');
    }
}
