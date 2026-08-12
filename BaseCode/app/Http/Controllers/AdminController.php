<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
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
                'reports' => Report::where('status', 'pending')->count(),
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
        $oldStatus = $user->status;
        $user->status = $user->status === 'active' ? 'locked' : 'active';
        $user->save();
        $action = $user->status === 'locked' ? 'lock_user' : 'unlock_user';
        $actionText = $user->status === 'locked' ? 'Khóa' : 'Mở khóa';
        //ghi log
        \App\Services\AuditLogger::log(
            $action,
            "{$actionText} tài khoản người dùng: {$user->email} (Trạng thái cũ: {$oldStatus})",
            true
        );

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái người dùng thành công.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Cấm xóa admin để tránh lỗi hệ thống (tùy chọn nhưng nên có)
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Không thể xóa Admin duy nhất của hệ thống.');
        }
        //ghi log
        \App\Services\AuditLogger::log(
            'delete_user',
            "Xoá vĩnh viễn tài khoản người dùng: {$user->email}",
            true
        );

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

        //ghi log
        \App\Services\AuditLogger::log(
            'reject_post',
            "Từ chối bài đâng phòng trọ:" . ($post->title ?? "ID # {$id}") . " . Lý do: " . $request->reject_reason,
            false
        );

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
        //ghi log
        \App\Services\AuditLogger::log(
            'approve_post',
            "Phê duyệt bài đăng phòng trọ:" . ($post->title ?? "ID#{$id}"),
            false
        );
        return redirect()->back()->with('success', 'Đã phê duyệt và xuất bản tin đăng trọ thành công');
    }

    public function categories()
    {
        return Inertia::render('Admin/Category/index');
    }

    //Phần tiếp nhận báo cáo
    public function reports()
    {
        $negotiationDays = \App\Models\Setting::where('key', 'report_negotiation_days')->value('value') ?? 2;

        //lấy toàn bộ báo cáo cùng các quan hệ liên quan
        $reports = Report::with(['reportable', 'reporter', 'resolver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($report) {
                // Tạo tên hiển thị cho đối tượng bị báo cáo (phòng trọ, hóa đơn, hợp đồng)
                $targetText = 'N/A';
                if ($report->reportable) {
                    if ($report->reportable_type === \App\Models\Room::class) {
                        $targetText = 'Phòng ' . $report->reportable->room_number . ' - ' . ($report->reportable->boardingHouse->name ?? '');
                    } elseif ($report->reportable_type === \App\Models\Invoice::class) {
                        $targetText = 'Hóa đơn #' . $report->reportable->invoice_code;
                    } elseif ($report->reportable_type === \App\Models\Contract::class) {
                        $targetText = 'Hợp đồng #' . $report->reportable->id;
                    } else {
                        $targetText = class_basename($report->reportable_type) . ' #' . $report->reportable->id;
                    }
                }

                $isExpired = $report->negotiation_deadline ? now()->gt($report->negotiation_deadline) : false;
                $canAdminResolve = ($isExpired && $report->status === 'pending') || ($report->status === 'pending' && $report->target_resolved);

                return [
                    'id' => $report->id,
                    'from' => $report->reporter->name ?? 'Người dùng ẩn danh',
                    'fromEmail' => $report->reporter->email ?? '',
                    'target' => $targetText,
                    'reason' => $report->reason,
                    'description' => $report->description,
                    'date' => $report->created_at->format('d/m/Y'),
                    'status' => $report->status, // pending, investigating, resolved, rejected
                    'note' => $report->admin_note ?? '',
                    'is_expired' => $isExpired,
                    'can_admin_resolve' => $canAdminResolve,
                    'evidence_images' => $report->evidence_images ?? [],
                    'response_note' => $report->response_note ?? '',
                    'response_evidence' => $report->response_evidence ?? [],
                    'target_resolved' => (bool) $report->target_resolved,
                    'negotiation_deadline' => $report->negotiation_deadline ? $report->negotiation_deadline->format('d/m/Y H:i') : null,
                ];
            });

        return Inertia::render('Admin/Reports/index', [
            'reports' => $reports,
            'negotiationDays' => (int) $negotiationDays
        ]);
    }

    //phương thức cập nhật trạng thái báo cáo cho admin
    public function updateReport(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:resolved,ignored,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $report = Report::with('reportable.boardingHouse.user', 'reporter')->findOrFail($id);

        $report->update([
            'status' => $request->status,
            'admin_note' => $request->admin_note,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);
        //ghi log
        $action = $request->status === 'resolved' ? 'resolved_report' : 'ignore_report';
        $statusText = $request->status === 'resolved' ? 'Chấp nhận giải quyết' : ($request->status === 'ignored' ? 'Bỏ qua' : 'Từ chối');
        $noteText = $request->admin_note ? " (Ghi chú: " . $request->admin_note . ")" : "";
        $reporterEmail = $report->reporter ? $report->reporter->email : "N/A";
        \App\Services\AuditLogger::log(
            $action,
            "{$statusText} khiếu nại báo cáo ID #{$report->id} của khách thuê: {$reporterEmail}{$noteText}",
            true
        );
        if ($request->status === 'resolved') {
            $statusLabel = 'đã được giải quyết (Chấp nhận khiếu nại)';
        } elseif ($request->status === 'ignored' || $request->status === 'rejected') {
            $statusLabel = 'bị từ chối / bỏ qua khiếu nại';
        }

        // 1. Thông báo cho Khách thuê (Người báo cáo)
        $reporter = $report->reporter;
        if ($reporter) {
            $reporter->notify(new \App\Notifications\AdminNotification(
                'Admin đã xử lý khiếu nại của bạn',
                'Báo cáo #' . $report->id . ' của bạn đã được Admin xử lý: ' . $statusLabel,
                'report_admin_action',
                '/profile/listbaocao'
            ));
        }

        // 2. Thông báo cho Chủ trọ của phòng
        if ($report->reportable_type === \App\Models\Room::class) {
            $landlord = $report->reportable->boardingHouse->user ?? null;
            if ($landlord) {
                $landlord->notify(new \App\Notifications\AdminNotification(
                    'Kết quả xử lý khiếu nại từ Admin',
                    'Khiếu nại #' . $report->id . ' tại phòng ' . ($report->reportable->room_number ?? '') . ' ' . $statusLabel,
                    'report_admin_action_landlord',
                    '/landlord/reports'
                ));
            }
        }
        //phát sự kiện realtime với khách thuê & chủ trọ
        event(new \App\Events\ReportUpdated($report));
        return redirect()->back()->with('success', 'Cập nhật trạng thái báo cáo thành công');
    }

    public function updateReportDays(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30'
        ]);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'report_negotiation_days'],
            ['value' => $request->days]
        );

        return redirect()->back()->with('success', 'Cập nhật thời hạn thương lượng thành công.');
    }

    //CRUD lý do cho Admin
    public function reportReasons()
    {
        $reasons = \App\Models\ReportReason::orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/ReportReasons/Index', [
            'reasons' => $reasons
        ]);
    }

    public function storeReportReason(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255|unique:report_reasons,reason',
        ], [
            'reason.required' => 'Vui lòng nhập lý do báo cáo.',
            'reason.unique' => 'Lý do báo cáo này đã tồn tại.'
        ]);

        \App\Models\ReportReason::create([
            'reason' => $request->reason,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Thêm lý do báo cáo thành công.');
    }

    public function updateReportReason(Request $request, $id)
    {
        $reason = \App\Models\ReportReason::findOrFail($id);
        $request->validate([
            'reason' => 'required|string|max:255|unique:report_reasons,reason,' . $id,
            'is_active' => 'required|boolean'
        ]);
        $reason->update([
            'reason' => $request->reason,
            'is_active' => $request->is_active
        ]);
        return redirect()->back()->with('success', 'Cập nhật lý do báo cáo thành công.');
    }

    public function destroyReportReason($id)
    {
        $reason = \App\Models\ReportReason::findOrFail($id);
        $reason->delete();
        return redirect()->back()->with('success', 'xoá lý do báo cáo thành công');
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

    public function auditlog(Request $request)
    {
        //khởi tạo truy vấn log kèm thông tin User
        $query = \App\Models\AuditLog::with('user:id,name')
            ->orderBy('created_at', 'desc');

        //tìm kiếm theo từ khoá (tên user, IP,Nội dung)
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('target', 'like', "%{$q}")->orWhere('ip_address', 'like', "%{$q}")->orWhereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%");
                });
            });
        }
        //lọc theo mức độ của hành động
        if ($request->filled('type') && $request->type !== 'all') {
            if ($request->type === 'sensitive') {
                $query->where('sensitive', true);
            } else {
                $query->where('action', $request->type);
            }
        }
        //phân trang
        $logs = $query->paginate(15)->withQueryString();
        return Inertia::render('Admin/AuditLog/index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'type'])
        ]);
    }

    public function website()
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->map(function ($val) {
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
            //cấu hình thời hạn báo cáo
            'report_negotiation_days' => 'required|integer|min:1|max:30',
            'warning_electricity_price' => 'required|numeric|min:0',
            'warning_invoice_amount' => 'required|numeric|min:0',
            'warning_water_price' => 'required|numeric|min:0',
            'warning_monthly_rent' => 'required|numeric|min:0',
            'not_interested_reasons' => 'nullable|array',
        ], [
            'hero_title.required' => 'Tiêu đề chính không được để trống.',
            'hero_subtitle.required' => 'Mô tả phụ không được để trống.',
            'contact_phone.required' => 'Số điện thoại không được để trống.',
            'contact_email.required' => 'Email không được để trống.',
            'contact_email.email' => 'Email không đúng định dạng.',
            'contact_address.required' => 'Địa chỉ không được để trống.',
            'report_negotiation_days.required' => 'Thời hạn thương lượng không được để trống.',
            'report_negotiation_days.integer' => 'Thời hạn thương lượng phải là số nguyên.',
            'report_negotiation_days.min' => 'Thời hạn tối thiểu là 1 ngày.',
            'warning_electricity_price.required' => 'Ngưỡng giá điện không được để trống',
            'warning_electricity_price.numeric' => 'Ngưỡng giá điện phải là số.',
            'warning_electricity_price.min' => 'Ngưỡng giá điện không được nhỏ hơn 0',
            'warning_water_price.required' => 'Ngưỡng giá nước không được để trống.',
            'warning_water_price.numeric' => 'Ngưỡng giá nước phải là số.',
            'warning_water_price.min' => 'Ngưỡng giá nước không được nhỏ hơn 0.',
            'warning_invoice_amount.required' => 'Ngưỡng tổng tiền hóa đơn không được để trống.',
            'warning_invoice_amount.numeric' => 'Ngưỡng tổng tiền hóa đơn phải là số.',
            'warning_invoice_amount.min' => 'Ngưỡng tổng tiền hóa đơn không được nhỏ hơn 0.',
            'warning_monthly_rent.required' => 'Ngưỡng tiền thuê phòng không được để trống.',
            'warning_monthly_rent.numeric' => 'Ngưỡng tiền thuê phòng phải là số.',
            'warning_monthly_rent.min' => 'Ngưỡng tiền thuê phòng không được nhỏ hơn 0.',
        ]);
        \App\Models\Setting::updateOrCreate(['key' => 'report_negotiation_days'], ['value' => $request->report_negotiation_days]);
        \App\Models\Setting::updateOrCreate(['key' => 'hero_title'], ['value' => $request->hero_title]);
        \App\Models\Setting::updateOrCreate(['key' => 'hero_subtitle'], ['value' => $request->hero_subtitle]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_phone'], ['value' => $request->contact_phone]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_email'], ['value' => $request->contact_email]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_address'], ['value' => $request->contact_address]);
        \App\Models\Setting::updateOrCreate(['key' => 'contact_map'], ['value' => $request->contact_map]);
        \App\Models\Setting::updateOrCreate(['key' => 'warning_electricity_price'], ['value' => $request->warning_electricity_price]);
        \App\Models\Setting::updateOrCreate(['key' => 'warning_water_price'], ['value' => $request->warning_water_price]);
        \App\Models\Setting::updateOrCreate(['key' => 'warning_invoice_amount'], ['value' => $request->warning_invoice_amount]);
        \App\Models\Setting::updateOrCreate(['key' => 'warning_monthly_rent'], ['value' => $request->warning_monthly_rent]);
        \App\Models\Setting::updateOrCreate(['key' => 'not_interested_reasons'], ['value' => json_encode($request->input('not_interested_reasons', []), JSON_UNESCAPED_UNICODE)]);
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
            $banner['order'] = (int) $banner['order'];
        }

        \App\Models\Setting::updateOrCreate(['key' => 'banners'], ['value' => json_encode(array_values($banners), JSON_UNESCAPED_UNICODE)]);

        //ghi log
        \App\Services\AuditLogger::log(
            'update_website',
            "Cập nhật lại toàn bộ cấu hình hiển thị và thông tin của website chính",
            true
        );

        return redirect()->back()->with('success', 'Đã cập nhật cấu hình giao diện website thành công!');
    }

    public function ads()
    {
        return Inertia::render('Admin/Ads/index');
    }
}
