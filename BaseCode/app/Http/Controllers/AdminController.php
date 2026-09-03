<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use App\Models\RoomPost;
use App\Services\RoomListingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Notifications\AccountStatusNotification;
use Illuminate\Support\Facades\Notification;


class AdminController extends Controller
{
    protected $roomPostService;

    public function __construct(RoomListingService $roomPostService)
    {
        $this->roomPostService = $roomPostService;
    }
    public function index()
    {
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $pendingApproval = RoomPost::where('status', 'pending')->count();
        $pendingBoardingHouses = \App\Models\BoardingHouse::where('status', 'pending')->count();
        $reports = Report::where('status', 'pending')->count();
        $totalLandlords = User::where('role', 'landlord')->count();

        // 5 Người dùng đăng ký mới nhất từ DB
        $recentUsers = User::where('role', '!=', 'admin')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name ?? 'Người dùng',
                    'email' => $u->email,
                    'avatar' => $u->avatar,
                    'role' => $u->role === 'landlord' ? 'Chủ trọ' : 'Người thuê',
                    'date' => $u->created_at->format('d/m/Y'),
                    'status' => $u->status ?? 'active',
                ];
            });

        // 5 Báo cáo mới nhất từ DB
        $recentReports = Report::with(['reporter'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'from' => $r->reporter->name ?? 'Khách',
                    'target' => $r->target_type ?? 'Tin đăng',
                    'type' => $r->reason ?? 'Vi phạm',
                    'date' => $r->created_at->format('d/m/Y'),
                    'status' => $r->status ?? 'pending',
                ];
            });

        // Biểu đồ đăng ký 12 tháng gần nhất từ DB
        $startDate = now()->subMonths(11)->startOfMonth();
        $userStats = User::where('created_at', '>=', $startDate)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($item) => $item->year . '-' . sprintf('%02d', $item->month));
        $months = [];
        $monthlyCounts = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonth($i);
            $key = $date->year . '-' . sprintf('%02d', $date->month);
            $months[] = 'T' . $date->month;
            $monthlyCounts[] = isset($userStats[$key]) ? (int) $userStats[$key]->count : 0;
        }

        return Inertia::render('Admin/dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'newUsersToday' => $newUsersToday,
                'pendingApproval' => $pendingApproval,
                'pendingBoardingHouses' => $pendingBoardingHouses,
                'reports' => $reports,
                'totalLandlords' => $totalLandlords,
            ],
            'recentUsers' => $recentUsers,
            'recentReports' => $recentReports,
            'monthlyChart' => [
                'months' => $months,
                'counts' => $monthlyCounts,
            ]
        ]);
    }

    public function users()
    {
        $users = User::with(['propertyManagers.boardingHouse.user'])
            ->where('role', '!=', 'admin')
            ->select(['id', 'name', 'email', 'phone', 'avatar', 'role', 'status', 'lock_reason', 'last_profile_update_at', 'profile_unlock_reason', 'profile_unlock_requested_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();
        return Inertia::render('Admin/Users/index', [
            'users' => $users
        ]);
    }

    public function toggleUserStatus(Request $request, $id)
    {

        $user = User::findOrFail($id);
        $oldStatus = $user->status ?? 'active';
        $isLocking = $oldStatus === 'active';
        $adminContactEmail = \App\Models\Setting::where('key', 'contact_email')->value('value') ?? 'support@ninhbinhstaywork.vn';
        if ($isLocking) {
            $request->validate([
                'reason' => 'required|string|max:1000'
            ], [
                'reason.required' => 'Vui lòng nhập lý do khóa tài khoản.'
            ]);
            $user->status = 'locked';
            $user->lock_reason = trim($request->input('reason'));
        } else {
            $user->status = 'active';
            $user->lock_reason = null;
        }

        $user->save();

        // tài khoản bị khóa và là chủ trọ -> tự động ẩn hoặc khôi phục tin đăng của chủ trọ
        if ($user->role === 'landlord') {
            if ($user->status === 'locked') {
                //khi khoá: tạm ẩn tất cả tin đăng đang hiển thị
                \App\Models\RoomPost::where('landlord_id', $user->id)
                    ->where('status', 'approved')
                    ->update(['status' => 'hidden']);
            } else if ($user->status === 'active') {
                //khi mở khoá: tự động khôi phục tất cả tin đăng đã bị ẩn trước đó
                \App\Models\RoomPost::where('landlord_id', $user->id)
                    ->where('status', 'hidden')
                    ->update(['status' => 'approved']);
            }
        }

        $action = $user->status === 'locked' ? 'lock_user' : 'unlock_user';
        $actionText = $user->status === 'locked' ? 'Khóa' : 'Mở khóa';

        // gửi mail thông báo cho user/chủ trọ và admin
        try {
            $user->notify(new AccountStatusNotification($user->status, $user->lock_reason, $adminContactEmail));

            \Illuminate\Support\Facades\Notification::route('mail', $adminContactEmail)
                ->notify(new AccountStatusNotification($user->status, "[GIÁM SÁT ADMIN] Đã {$actionText} tài khoản: {$user->email}. Lý do: {$user->lock_reason}", $adminContactEmail));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gửi mail thông báo khóa tài khoản thất bại: " . $e->getMessage());
        }

        $logMsg = "{$actionText} tài khoản người dùng: {$user->email} (Trạng thái cũ: {$oldStatus})";
        if ($user->status === 'locked') {
            $logMsg .= ". Lý do khóa: {$user->lock_reason}";
        }

        //ghi log
        \App\Services\AuditLogger::log($action, $logMsg, true);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái người dùng thành công.');
    }
    //cấp quyền cho phép tài khoàn người dùng cập nhật lại thông tin cá nhân
    public function unlockUserProfile($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'last_profile_update_at' => null,
            'profile_unlock_reason' => null,
            'profile_unlock_requested_at' => null,
        ]);

        // Gửi thông báo cho người dùng
        try {
            $user->notify(new \App\Notifications\ProfileUnlockedNotification());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gửi thông báo mở khóa hồ sơ thất bại: " . $e->getMessage());
        }

        \App\Services\AuditLogger::log(
            'unlock_user_profile',
            "Admin cấp quyền cho tài khoản {$user->email} ({$user->name}) được phép cập nhật lại thông tin cá nhân 1 lần nữa.",
            true
        );

        return redirect()->back()->with('success', "Đã mở khóa cho tài khoản {$user->name} cập nhật lại thông tin cá nhân!");
    }

    public function rejectUnlockProfile($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'profile_unlock_reason' => null,
            'profile_unlock_requested_at' => null,
        ]);

        \App\Services\AuditLogger::log(
            'reject_unlock_profile',
            "Admin từ chối yêu cầu xin mở khóa thông tin cá nhân của tài khoản {$user->email} ({$user->name}).",
            true
        );

        return redirect()->back()->with('success', "Đã từ chối yêu cầu xin sửa thông tin của tài khoản {$user->name}!");
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
            ->with([
                'verification',
                'activeSubscription.plan',
                'boardingHouse' => function ($q) {
                    $q->withCount('rooms');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                //lấy kết quả từ thuộc tính rooms_count được tự động đếm sẵn
                $roomCount = $user->boardingHouse->rooms_count ?? 0;
                //lấy tên gói dịch vụ miễn phí
                $planName = $user->activeSubscription->plan->name ?? 'Miễn phí';
                return [
                    'id' => $user->id,
                    'avatar' => $user->avatar,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'Chưa cập nhật',
                    'cccd' => $user->cccd_number ?? ($user->verification->id_card_number ?? 'chưa cập nhật'),
                    'rooms' => $roomCount,
                    'plan' => $planName,
                    'boarding_house_name' => $user->boardingHouse->name ?? 'Chưa cấu hình',
                    'verified' => true,
                    'joined' => $user->created_at->format('d/m/Y'),
                    'verification' => $user->verification,
                    'boarding_house' => $user->boardingHouse,
                ];
            });
        return Inertia::render('Admin/Landlords/index', [
            'landlords' => $landlords
        ]);
    }

    public function logKycAccess(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $adminName = auth()->user()->name ?? 'Admin';

        \App\Services\AuditLogger::log(
            'view_sensitive_kyc',
            "Admin {$adminName} đã mở xem ảnh CCCD/KYC nhạy cảm của tài khoản chủ trọ: {$targetUser->email}",
            true
        );

        return response()->json(['success' => true]);
    }

    public function approval(Request $request)
    {
        $status = $request->input('status', 'pending');

        $counts = [
            'pending' => RoomPost::where('status', 'pending')->count(),
            'approved' => RoomPost::where('status', 'approved')->count(),
            'rejected' => RoomPost::where('status', 'rejected')->count(),
        ];

        $query = RoomPost::with(['room.floor', 'room.boardingHouse', 'landlord']);

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $listings = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Approval/index', [
            'listings' => $listings,
            'counts' => $counts,
            'currentStatus' => $status,
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

        if ($post->room && $post->room->services) {
            $post->room->services->map(function ($service) {
                if ($service->pivot && !is_null($service->pivot->price)) {
                    $service->price = $service->pivot->price;
                }
                return $service;
            });
        }

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
                $targetFileUrl = null;
                $targetInfo = null;

                if ($report->reportable) {
                    if ($report->reportable_type === \App\Models\Room::class) {
                        $room = $report->reportable;
                        $targetText = 'Phòng ' . $room->room_number . ' - ' . ($room->boardingHouse->name ?? '');
                        $targetInfo = 'Giá phòng: ' . number_format($room->price ?? 0) . 'đ';
                    } elseif ($report->reportable_type === \App\Models\Invoice::class) {
                        $invoice = $report->reportable;
                        $targetText = 'Hóa đơn #' . ($invoice->invoice_code ?? $invoice->id);
                        $targetInfo = 'Tổng tiền: ' . number_format($invoice->total_amount ?? 0) . 'đ';
                        $contractFile = $invoice->contract->contract_file_path ?? null;
                        if ($contractFile) {
                            $targetFileUrl = (str_starts_with($contractFile, 'http') || str_starts_with($contractFile, '/storage/'))
                                ? $contractFile
                                : '/storage/' . ltrim($contractFile, '/');
                        }
                    } elseif ($report->reportable_type === \App\Models\Contract::class) {
                        $contract = $report->reportable;
                        $targetText = 'Hợp đồng #' . $contract->id;
                        $targetInfo = 'Tiền nhà: ' . number_format($contract->monthly_rent ?? 0) . 'đ | Cọc: ' . number_format($contract->deposit_amount ?? 0) . 'đ';
                        $contractFile = $contract->contract_file_path ?? null;
                        if ($contractFile) {
                            $targetFileUrl = (str_starts_with($contractFile, 'http') || str_starts_with($contractFile, '/storage/'))
                                ? $contractFile
                                : '/storage/' . ltrim($contractFile, '/');
                        }
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
                    'target_info' => $targetInfo,
                    'target_file_url' => $targetFileUrl,
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
        $landlord = null;
        $request->validate([
            'status' => 'required|in:resolved,ignored,rejected',
            'admin_note' => 'required|string|min:5|max:1000',
        ], [
            'status.required' => 'Vui lòng chọn trạng thái xử lý.',
            'status.in' => 'Trạng thái xử lý không hợp lệ.',
            'admin_note.required' => 'Vui lòng nhập lý do / ghi chú xử lý của Admin.',
            'admin_note.min' => 'Lý do / ghi chú xử lý phải dài tối thiểu 5 ký tự.',
            'admin_note.max' => 'Lý do / ghi chú xử lý không được vượt quá 1000 ký tự.',
        ]);

        $report = Report::findOrFail($id);
        //nạp quan hệ động tuỳ theo báo cái hay hoá đơn
        if ($report->reportable_type === \App\Models\Room::class) {
            $report->load(['reportable.boardingHouse.user', 'reporter']);
        } elseif ($report->reportable_type === \App\Models\Invoice::class) {
            $report->load(['reportable.contract.room.boardingHouse.user', 'reporter']);
        } else {
            $report->load(['reporter']);
        }

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
                '/reports'
            ));
        }

        // 2. Thông báo cho Chủ trọ của phòng
        if ($report->reportable_type === \App\Models\Room::class) {
            $landlord = $report->reportable->boardingHouse->user ?? null;
        } elseif ($report->reportable_type === \App\Models\Invoice::class) {
            $landlord = $report->reportable->contract->room->boardingHouse->user ?? null;
        }
        if ($landlord) {
            $landlord->notify(new \App\Notifications\AdminNotification(
                'kết quả xử lý khiếu nại từ Admin',
                'Khiếu nại #' . $report->id . '' . $statusLabel,
                'report_admin_action_landlord',
                '/landlord/reports'
            ));
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
        if ($reasons->isEmpty()) {
            $defaults = [
                'Hỏng hóc thiết bị / Sự cố điện nước',
                'Thành viên khác vi phạm nội quy / Ồn ào',
                'Sự cố về Hợp đồng / Tiền trọ',
                'Không minh bạch về chi phí dịch vụ',
                'Khác'
            ];
            foreach ($defaults as $item) {
                \App\Models\ReportReason::firstOrCreate(['reason' => $item], ['is_active' => true]);
            }
            $reasons = \App\Models\ReportReason::orderBy('created_at', 'desc')->get();
        }
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
        $reviews = \App\Models\Review::with(['tenant', 'room.boardingHouse', 'appointment.room.boardingHouse'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) {
                $roomName = 'Chưa xác định';
                if ($r->room) {
                    $bName = $r->room->boardingHouse->name ?? '';
                    $rName = $r->room->name ?? ('Phòng ' . ($r->room->room_number ?? $r->room->id));
                    $roomName = $bName ? "{$bName} - {$rName}" : $rName;
                } elseif ($r->appointment && $r->appointment->room) {
                    $bName = $r->appointment->room->boardingHouse->name ?? '';
                    $rName = $r->appointment->room->name ?? ('Phòng ' . ($r->appointment->room->room_number ?? $r->appointment->room->id));
                    $roomName = $bName ? "{$bName} - {$rName}" : $rName;
                }

                return [
                    'id' => $r->id,
                    'reviewer' => $r->tenant->name ?? 'Người dùng',
                    'room' => $roomName,
                    'stars' => (int) $r->rating,
                    'content' => $r->comment ?? '',
                    'date' => $r->created_at ? $r->created_at->format('d/m/Y') : '',
                    'visible' => (bool) $r->is_visible,
                ];
            });

        return Inertia::render('Admin/Reviews/index', [
            'reviews' => $reviews
        ]);
    }

    public function toggleReviewVisibility($id)
    {
        $review = \App\Models\Review::findOrFail($id);
        $review->is_visible = !$review->is_visible;
        $review->save();

        return redirect()->back()->with('success', 'Cập nhật trạng thái hiển thị đánh giá thành công!');
    }

    public function deleteReview($id)
    {
        $review = \App\Models\Review::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'Xóa đánh giá thành công!');
    }

    //phần trang nguồn thu của admin
    public function revenue()
    {
        $currentYear = (int) date('Y');
        //tổng doang thu tích luỹ từ các đơn mua gói đã duyệt
        $totalRevenue = (float) \App\Models\LandlordSubscription::whereIn('status', ['approved', 'active'])
            ->sum('price_at_purchase');
        //doanh thu tháng hiện tại 
        $thisMonthRevenue = (float) \App\Models\LandlordSubscription::whereIn('status', ['approved', 'active'])
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->sum('price_at_purchase');
        //số lượng chủ trọ trả phí
        $paidCount = \App\Models\LandlordSubscription::whereIn('status', ['approved', 'active'])
            ->where('price_at_purchase', '>', 0)
            ->distinct('user_id')
            ->count('user_id');
        //số lượng chủ trọ dùng thử miễn phí
        $freeCount = \App\Models\User::where('role', 'landlord')
            ->whereDoesntHave('subscriptions', function ($q) {
                $q->whereIn('status', ['approved', 'active'])
                    ->where('price_at_purchase', '>', 0);
            })
            ->count();
        //doanh thu 12 tháng năm hiện tại dùng cho biểu đồ
        $monthlyRevenue = [];
        for ($m = 1; $m <= 12; $m++) {
            $sum = (float) 
                \App\Models\LandlordSubscription::whereIn('status', ['approved', 'active'])
                    ->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $m)
                    ->sum('price_at_purchase');
            $monthlyRevenue[] = $sum;
        }
        //các gói dịch vụ đang hoạt động
        $plans = \App\Models\SubscriptionPlan::with('features')->where('is_active', true)->orderBy('price')->get();
        // lịch sử giao dịch mua gói phân trang 10 giao dịch / trang
        $transactions = \App\Models\LandlordSubscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($sub) {
                return [
                    'id' => $sub->id,
                    'landlord' => $sub->user->name ?? 'Chủ trọ',
                    'plan' => $sub->plan->name ?? 'Gói dịch vụ',
                    'amount' => (float) ($sub->price_at_purchase ?? 0),
                    'date' => $sub->created_at ? $sub->created_at->format('d/m/Y') : '',
                    'status' => ($sub->status === 'approved' || $sub->status === 'active') ? 'paid' : ($sub->price_at_purchase == 0 ? 'free' : $sub->status),
                ];
            });
        return Inertia::render('Admin/Revenue/index', [
            'totalRevenue' => $totalRevenue,
            'thisMonthRevenue' => $thisMonthRevenue,
            'paidCount' => $paidCount,
            'freeCount' => $freeCount,
            'monthlyRevenue' => $monthlyRevenue,
            'plans' => $plans,
            'transactions' => $transactions,
        ]);
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
                $sub->where('target', 'like', "%{$q}%")->orWhere('ip_address', 'like', "%{$q}%")->orWhereHas('user', function ($u) use ($q) {
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
            return is_array($decoded) ? array_values($decoded) : $val;
        });

        return Inertia::render('Admin/WebEditor/index', [
            'initialSettings' => $settings
        ]);
    }

    public function updateWebsite(Request $request)
    {
        $disk = (config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret')) ? 'r2_public' : 'public';
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
        $rawReasons = $request->input('not_interested_reasons', []);
        $reasonsArray = array_values(array_filter((array) $rawReasons, function ($r) {
            return !is_null($r) && trim($r) !== '';
        }));
        \App\Models\Setting::updateOrCreate(['key' => 'not_interested_reasons'], ['value' => json_encode($reasonsArray, JSON_UNESCAPED_UNICODE)]);
        $banners = $request->input('banners', []);
        $files = $request->file('banners');

        $useR2 = config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret');
        $r2Url = rtrim(config('filesystems.disks.r2_public.url') ?? env('CLOUDFLARE_R2_PUBLIC_URL', ''), '/');

        if (is_array($files)) {
            foreach ($banners as $index => &$banner) {
                if (isset($files[$index]['file']) && $files[$index]['file']->isValid()) {
                    $path = $files[$index]['file']->store('banners', $disk);
                    if ($useR2 && !empty($r2Url)) {
                        $banner['img'] = $r2Url . '/' . ltrim($path, '/');
                    } else {
                        $banner['img'] = '/storage/' . ltrim($path, '/');
                    }
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
}
