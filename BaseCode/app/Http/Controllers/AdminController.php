<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RoomPost;
use App\Services\RoomListingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    protected $roomPostService;

    public function __construct(RoomListingService $roomPostService)
    {
        $this->roomPostService = $roomPostService;
    }
    public function index()
    {
        return Inertia::render('Admin/dashboard', [
            'stats' => [
                'totalUsers' => User::count(),
                'newUsersToday' => User::whereDate('created_at', today())->count(),
                'pendingApproval' => RoomPost::where('status', 'pending')->count(),
                'reports' => 0,
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
        $landlords = User::where('role', 'landlord')
            ->with(['verification', 'boardingHouse'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                $roomCount = 0;
                if ($user->boardingHouse) {
                    $roomCount = \Illuminate\Support\Facades\DB::table('rooms')
                        ->where('boarding_house_id', $user->boardingHouse->id)
                        ->count();
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'Chưa cập nhật',
                    'cccd' => $user->cccd_number ?? ($user->verification->id_card_number ?? 'Chưa cập nhật'),
                    'rooms' => $roomCount,
                    'plan' => 'Miễn phí', // Logic gói dịch vụ có thể mở rộng sau
                    'boarding_house_name' => $user->boardingHouse->name ?? 'Chưa cấu hình',
                    'verified' => true,       // Vì role=landlord nên chắc chắn đã xác minh
                    'joined' => $user->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('Admin/Landlords/index', [
            'landlords' => $landlords
        ]);
    }

    public function approval()
    {
        $listings = RoomPost::with(['room.floor', 'room.boardingHouse', 'landlord'])
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->get();
        return Inertia::render('Admin/Approval/index', [
            'listings' => $listings
        ]);
    }

    //Phần admin từ chối duyệt kèm lý do hiển thị từ popup
    public function rejectListing(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'required|string|min:5|max:255'
        ], [
            'reject_reason.required' => 'Vui lòng nhập lý do cụ thể để chủ trọ biết để chỉnh sửa',
            'reject_reason.min' => 'Lý do quá ngắn, vui lòng nhập ít nhất 5 ký tự.'
        ]);
        $post = RoomPost::findOrFail($id);
        $this->roomPostService->rejectPost($post, $request->reject_reason);
        return redirect()->back()->with('success', 'đã từ chối bài viết và gửi lỗi đến chủ trọ');
    }

    //Phần xem chi tiết tin đăng của Admin
    public function showApproval($id)
    {
        //Eager load đầy đủ thông tin phòng, tầng, khu nhà trọ và thông tin của chủ trọ
        $post = RoomPost::with(['room.floor', 'room.boardingHouse', 'landlord', 'room.services'])
            ->findOrFail($id);
        //trả dữ liệu ra đúng file Show.vue
        return Inertia::render('Admin/Approval/Show', [
            'post' => $post
        ]);
    }

    public function approveListing($id)
    {
        //tìm bài viết theo ID
        $post = RoomPost::findOrFail($id);
        //gọi services xử lý rồi đẩy sang thông báo
        $this->roomPostService->approvePost($post);
        return redirect()->back()->with('success', 'Đã phê duyệt và xuất bản tin đăng trọ thành công');
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
        $settings = \App\Models\Setting::pluck('value', 'key')->map(function($val) {
            $decoded = json_decode($val, true);
            return is_array($decoded) ? $decoded : $val;
        });

        return Inertia::render('Admin/WebEditor/index', [
            'initialSettings' => $settings
        ]);
    }

    public function updateWebsite(Request $request)
    {
        $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'required|string|max:500',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:100',
            'contact_address' => 'required|string|max:255',
            'contact_map' => 'nullable|string|max:1000',
            'banners' => 'nullable|array',
        ], [
            'hero_title.required' => 'Tiêu đề chính không được để trống.',
            'hero_subtitle.required' => 'Mô tả phụ không được để trống.',
            'contact_phone.required' => 'Số điện thoại không được để trống.',
            'contact_email.required' => 'Email không được để trống.',
            'contact_email.email' => 'Email không đúng định dạng.',
            'contact_address.required' => 'Địa chỉ không được để trống.',
        ]);

        \App\Models\Setting::updateOrCreate(['key' => 'hero_title'], ['value' => $request->hero_title]);
        \App\Models\Setting::updateOrCreate(['key' => 'hero_subtitle'], ['value' => $request->hero_subtitle]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_phone'], ['value' => $request->contact_phone]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_email'], ['value' => $request->contact_email]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_address'], ['value' => $request->contact_address]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_map'], ['value' => $request->contact_map]);

        $banners = $request->input('banners', []);
        $files = $request->file('banners');

        if (is_array($files)) {
            foreach ($banners as $index => &$banner) {
                if (isset($files[$index]['file']) && $files[$index]['file']->isValid()) {
                    $path = $files[$index]['file']->store('banners', 'public');
                    $banner['img'] = '/storage/' . $path;
                }
            }
        }

        foreach ($banners as &$banner) {
            unset($banner['file']);
            // Đảm bảo active là boolean
            $banner['active'] = filter_var($banner['active'], FILTER_VALIDATE_BOOLEAN);
            // Đảm bảo order là integer
            $banner['order'] = (int)$banner['order'];
        }

        \App\Models\Setting::updateOrCreate(['key' => 'banners'], ['value' => json_encode(array_values($banners), JSON_UNESCAPED_UNICODE)]);

        return redirect()->back()->with('success', 'Đã cập nhật cấu hình giao diện website thành công!');
    }

    public function ads()
    {
        return Inertia::render('Admin/Ads/index');
    }
}
