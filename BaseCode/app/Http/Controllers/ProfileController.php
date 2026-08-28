<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UserUpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\RoommateRequest;
use App\Models\Contract;
use App\Models\RoomResident;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use function Safe\strtotime;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    protected $profileService;

    /**
     * Constructor injection for ProfileService
     */
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display the user's profile form.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $profileData = $this->profileService->getProfileData($user);
        //lấy danh sách các lý do báo cáo đang active
        $reasons = \App\Models\ReportReason::where('is_active', true)->get();
        return Inertia::render('Profile/tranguser', [
            'user' => $user,
            'rentalStatus' => $profileData['rentalStatus'],
            'accountStatus' => $profileData['accountStatus'],
            'canUpdateProfile' => $profileData['canUpdateProfile'] ?? true,
            'daysUntilNextUpdate' => $profileData['daysUntilNextUpdate'] ?? 0,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    //trang quản lý nơi ở
    public function quanlynoio(Request $request): Response
    {
        $userId = $request->user()->id;
        $isPrimaryTenant = false;
        // Tìm hợp đồng của người dùng đứng tên đại diện
        $contract = \App\Models\Contract::where('tenant_id', $userId)
            ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
            ->with(['room.boardingHouse.user', 'room.residents.user', 'invoices', 'tenant'])
            ->orderBy('created_at', 'desc')
            ->first();
        if ($contract) {
            $isPrimaryTenant = true; // Là Chủ hợp đồng
        } else {
            // Check nếu là thành viên ở ghép
            $resident = \App\Models\RoomResident::where('user_id', $userId)
                ->where('status', 'active')
                ->first();
            if ($resident) {
                $contract = \App\Models\Contract::where('room_id', $resident->room_id)
                    ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
                    ->with(['room.boardingHouse.user', 'room.residents.user', 'invoices', 'tenant'])
                    ->orderBy('created_at', 'desc')
                    ->first();
                $isPrimaryTenant = false; // Là Thành viên ở ghép
            }
        }
        //tự động kiểm tra và đồng bộ số người ở hiện tại của phòng
        if ($contract && $contract->room) {
            $room = $contract->room;
            $hasActiveContract = \App\Models\Contract::where('room_id', $room->id)
                ->whereIn('status', ['active', 'signed', 'awaiting_upload', 'termination_requested', 'expiring'])
                ->exists();
            $activeResidentsCount = \App\Models\RoomResident::where('room_id', $room->id)
                ->where('status', 'active')
                ->count();
            $realCurrentPeople = max(1, ($hasActiveContract ? 1 : 0) + $activeResidentsCount);
            if ((int) $room->current_people !== $realCurrentPeople) {
                $room->current_people = $realCurrentPeople;
            }
        }
        $reasons = \App\Models\ReportReason::where('is_active', true)->get();
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
            $reasons = \App\Models\ReportReason::where('is_active', true)->get();
        }
        return Inertia::render('Profile/qlynoio', [
            'user' => $request->user(),
            'contract' => $contract,
            'isPrimaryTenant' => $isPrimaryTenant,
            'reasons' => $reasons,
        ]);
    }

    //trang thanh toán
    public function lichsuthanhtoan(Request $request): Response
    {
        $user = $request->user();
        $residentRoomIds = \App\Models\RoomResident::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('room_id')
            ->toArray();

        $invoices = \App\Models\Invoice::whereHas('contract', function ($q) use ($user, $residentRoomIds) {
            $q->where('tenant_id', $user->id);
            if (!empty($residentRoomIds)) {
                $q->orWhereIn('room_id', $residentRoomIds);
            }
        })
            ->with(['details.service', 'contract.tenant', 'contract.room.boardingHouse.user', 'contract.room.residents.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $reasons = \App\Models\ReportReason::where('is_active', true)->get();
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
            $reasons = \App\Models\ReportReason::where('is_active', true)->get();
        }

        return Inertia::render('Profile/listthanhtoan', [
            'user' => $user,
            'invoices' => $invoices,
            'reasons' => $reasons,
        ]);
    }

    //trang cài đặt user
    public function caidatuser(Request $request): Response
    {
        return Inertia::render('Profile/caidat', [
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update user profile from tranguser page
     */
    public function updateProfile(UserUpdateProfileRequest $request): RedirectResponse
    {
        $this->profileService->updateProfile($request->user(), $request->validated());

        return Redirect::back()->with('status', 'profile-updated')->with('success', 'Cập nhật hồ sơ thành công.');
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ], [
            'avatar.required' => 'Vui lòng chọn một file ảnh.',
            'avatar.image' => 'File tải lên phải là ảnh.',
            'avatar.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'avatar.max' => 'Dung lượng ảnh không được vượt quá 2MB.',
        ]);

        if ($request->hasFile('avatar')) {
            $this->profileService->updateAvatar($request->user(), $request->file('avatar'));
        }

        return Redirect::back()->with('success', 'Cập nhật ảnh đại diện thành công.');
    }

    /**
     * Display client-side viewing appointments list
     */
    public function appointments(Request $request): Response
    {
        $appointments = \App\Models\Appointment::with([
            'room.boardingHouse.landlord',
            'room.roomPosts' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        // Tự động đồng bộ trạng thái thực tế với Hợp đồng & Cư dân ở ghép
        foreach ($appointments as $apt) {
            $userId = $request->user()->id;
            $roomId = $apt->room_id;

            // Check xem có hợp đồng đang có hiệu lực / đã ký đứng tên người này không
            $activeContract = \App\Models\Contract::where('room_id', $roomId)
                ->where('tenant_id', $userId)
                ->whereIn('status', ['active', 'signed'])
                ->latest()
                ->first();

            // Check xem có hợp đồng đã thanh lý đứng tên người này không
            $terminatedContract = \App\Models\Contract::where('room_id', $roomId)
                ->where('tenant_id', $userId)
                ->where('status', 'terminated')
                ->latest()
                ->first();

            // Check xem người này có đang ở ghép trong phòng không
            $isResident = \App\Models\RoomResident::where('room_id', $roomId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists();

            $pastResident = \App\Models\RoomResident::where('room_id', $roomId)
                ->where('user_id', $userId)
                ->where('status', 'inactive')
                ->exists();

            if ($activeContract) {
                // Nếu người này đứng tên chính hợp đồng active -> 'success_matched' hoặc 'became_main_tenant'
                $apt->status = 'success_matched';
            } else if ($terminatedContract && !$isResident) {
                // Nếu hợp đồng đã thanh lý và không còn ở trong phòng -> 'terminated'
                $apt->status = 'terminated';
            } else if ($isResident) {
                // Nếu đang là thành viên ở ghép trong phòng -> 'joined_roommate'
                $apt->status = 'joined_roommate';
            } else if ($pastResident) {
                $apt->status = 'roommate_removed';
            }

            // Lấy slug_with_hash của RoomPost liên quan đến phòng
            $roomPost = \App\Models\RoomPost::where('room_id', $roomId)->latest()->first();
            if (!$roomPost && $apt->room) {
                $roomPost = \App\Models\RoomPost::whereHas('room', function ($q) use ($apt) {
                    $q->where('boarding_house_id', $apt->room->boarding_house_id);
                })->latest()->first();
            }
            $apt->post_slug_or_id = $roomPost ? ($roomPost->slug_with_hash ?: $roomPost->id) : null;
        }

        $favoriteRoomIds = $request->user()->favoriteRooms()->pluck('rooms.id')->toArray();

        return Inertia::render('Profile/lichhen', [
            'user' => $request->user(),
            'appointments' => $appointments,
            'favoriteRoomIds' => $favoriteRoomIds
        ]);
    }

    /**
     * Display client-side favorited rooms list
     */
    public function favorites(Request $request): Response
    {
        $favoriteRooms = $request->user()->favoriteRooms()
            ->with(['property.landlord.boardingHouse'])
            ->orderBy('favorites.created_at', 'desc')
            ->get();

        return Inertia::render('Profile/yeuthich', [
            'user' => $request->user(),
            'favoriteRooms' => $favoriteRooms
        ]);
    }

    /**
     * Toggle favorite status for a room
     */
    public function toggleFavorite(Request $request, $roomId): RedirectResponse
    {
        $user = $request->user();

        // Kiểm tra xem phòng đã được yêu thích chưa
        if ($user->favoriteRooms()->where('room_id', $roomId)->exists()) {
            // Bỏ yêu thích
            $user->favoriteRooms()->detach($roomId);
            $message = 'Đã bỏ yêu thích phòng trọ này.';
        } else {
            // Thêm vào yêu thích
            $user->favoriteRooms()->attach($roomId);
            $message = 'Đã lưu phòng trọ vào danh sách quan tâm.';
        }

        return Redirect::back()->with('success', $message);
    }

    /**
     * Submit a review for a room after viewing it
     */
    public function submitReview(Request $request, \App\Models\Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        // Kiểm tra quyền (chỉ người tạo lịch mới được đánh giá)
        if ($appointment->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        // Kiểm tra xem lịch hẹn đã được đánh giá chưa
        if ($appointment->status === 'viewed') {
            return Redirect::back()->with('error', 'Lịch hẹn này đã được đánh giá.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        // Tạo đánh giá
        \App\Models\Review::create([
            'tenant_id' => $user->id,
            'room_id' => $appointment->room_id,
            'appointment_id' => $appointment->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Cập nhật trạng thái lịch hẹn thành 'viewed'
        $appointment->update(['status' => 'viewed']);

        return Redirect::back()->with('success', 'Đánh giá phòng trọ thành công!');
    }

    /**
     * Submit interest decision after viewing room
     */
    public function submitInterest(Request $request, \App\Models\Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if ($appointment->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        $request->validate([
            'result' => 'required|in:interested,not_interested',
            'reason' => 'nullable|string|max:255',
        ]);

        //cập nhật trạng thái lịch hẹn khi thay đổi
        $status = $request->result === 'interested' ? 'success_matched' : 'false_matched';

        $appointment->update([
            'status' => $status,
            'feedback_result' => $request->result,
            'feedback_reason' => $request->reason,
            'feedback_time' => now()
        ]);

        if ($request->result === 'interested') {
            $appointment->load(['user', 'room.boardingHouse']);
            $landlord = $appointment->room->boardingHouse->user ?? null;
            if ($landlord) {
                $landlord->notify(new \App\Notifications\TenantInterestedNotification($appointment));
            }
        }

        if ($request->filled('cccd')) {
            $user->update(['cccd_number' => $request->cccd]);
        }

        $aiData = null;
        if ($request->result === 'not_interested') {
            $publicListingService = app(\App\Services\PublicListingService::class);
            $aiData = $publicListingService->getAiAlternativeRecommendationsForAppointment($appointment, $request->reason);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã ghi nhận lựa chọn của bạn!',
                'status' => $status,
                'ai_recommendations' => $aiData
            ]);
        }

        return Redirect::back()->with([
            'success' => 'Đã ghi nhận lựa chọn của bạn!',
            'ai_recommendations' => $aiData
        ]);
    }

    /**
     * Lấy 3 phòng trọ tương tự từ Trợ lý AI cho lịch hẹn đã xem
     */
    public function getAiRecommendations(\App\Models\Appointment $appointment): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        if ($appointment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập thông tin này.',
                'rooms' => []
            ], 403);
        }

        $publicListingService = app(\App\Services\PublicListingService::class);
        $result = $publicListingService->getAiAlternativeRecommendationsForAppointment($appointment, $appointment->feedback_reason);

        return response()->json($result);
    }

    /**
     * Hủy đăng ký / đổi ý từ Ưng sang Hủy hợp đồng (gửi lý do tới chủ trọ phê duyệt)
     */
    public function cancelInterest(Request $request, \App\Models\Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if ($appointment->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $appointment->update([
            'feedback_result' => 'cancel_requested',
            'cancellation_reason' => $request->reason,
            'feedback_reason' => $request->reason,
            'feedback_time' => now(),
            'status' => 'cancel_requested',
        ]);

        //gửi thông báo cho chủ trọ
        $appointment->load(['user', 'room', 'landlord']);
        $landlord = $appointment->landlord ?? $appointment->room?->boardingHouse?->user ?? null;
        if ($landlord) {
            $landlord->notify(new \App\Notifications\TenantCancelledNotification($appointment));
        }

        // Nếu có hợp đồng liên quan chưa chính thức ký, cập nhật trạng thái hủy
        $contract = \App\Models\Contract::where('room_id', $appointment->room_id)
            ->where('tenant_id', $appointment->user_id)
            ->whereIn('status', ['draft', 'awaiting_upload', 'pending', 'signed', 'active', 'termination_requested'])
            ->first();

        if ($contract) {
            \App\Models\Contract::$allowImmutableUpdate = true;
            $contract->update([
                'status' => 'termination_requested',
                'cancellation_reason' => $request->reason,
                'cancelled_by' => $user->id
            ]);
            \App\Models\Contract::$allowImmutableUpdate = false;
        }

        return redirect()->back()->with('success', 'Đã gửi yêu cầu hủy đăng ký hợp đồng tới Chủ trọ thành công!');
    }

    /**
     * Gửi thông báo đã thanh toán cho chủ trọ
     */
    public function notifyPayment(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string|in:qr,cash'
        ]);

        $user = $request->user();
        $residentRoomIds = \App\Models\RoomResident::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('room_id')
            ->toArray();

        $invoice = \App\Models\Invoice::where('id', $id)
            ->whereHas('contract', function ($q) use ($user, $residentRoomIds) {
                $q->where('tenant_id', $user->id);
                if (!empty($residentRoomIds)) {
                    $q->orWhereIn('room_id', $residentRoomIds);
                }
            })
            ->with(['contract.room.boardingHouse.user'])
            ->firstOrFail();

        // Cập nhật trạng thái hoặc gửi thông báo
        // Giao dịch được coi là đã thông báo, chờ landlord duyệt
        $landlord = $invoice->contract->room->boardingHouse->user ?? null;
        if ($landlord) {
            $methodLabel = $request->payment_method === 'qr' ? 'QR Code' : 'Tiền mặt';
            $landlord->notify(new \App\Notifications\TenantPaidInvoiceNotification($invoice, $methodLabel));
        }

        return Redirect::back()->with('success', 'Đã gửi báo cáo thanh toán tới Chủ trọ! Hóa đơn sẽ CHỈ tự động đổi sang "Đã thanh toán" khi Ngân hàng xác nhận tiền đã về tài khoản hoặc Chủ trọ duyệt.');
    }

    /**
     * Yêu cầu chấm dứt hợp đồng từ phía Client (Người thuê)
     */
    public function requestTermination(Request $request, \App\Models\Contract $contract): RedirectResponse
    {
        $user = $request->user();

        if ($contract->tenant_id !== $user->id) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        \App\Models\Contract::$allowImmutableUpdate = true;
        $contract->update([
            'status' => 'termination_requested',
            'cancellation_reason' => $request->input('reason'),
            'cancelled_by' => $user->id
        ]);
        \App\Models\Contract::$allowImmutableUpdate = false;

        // Gửi thông báo đến Chủ trọ
        $landlord = $contract->room?->boardingHouse?->landlord;
        if ($landlord) {
            $roomNum = $contract->room?->room_number ?: '';
            $landlord->notify(new \App\Notifications\AdminNotification(
                'Yêu cầu chấm dứt hợp đồng mới',
                "Khách thuê {$user->name} vừa gửi yêu cầu chấm dứt hợp đồng phòng {$roomNum}. Lý do: {$request->input('reason')}",
                'warning',
                route('landlord.contracts')
            ));
        }

        return Redirect::back()->with('success', 'Đã gửi yêu cầu chấm dứt hợp đồng thành công. Vui lòng chờ chủ trọ xác nhận thanh lý.');
    }

    // Phần gia hạn hợp đồng từ client
    public function requestExtension(Request $request, \App\Models\Contract $contract): RedirectResponse
    {
        $user = $request->user();
        if ($contract->tenant_id !== $user->id) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }
        //ràng buộc check gửi gia hạn hợp đồng 1 lần
        if ($contract->cancellation_reason && str_contains($contract->cancellation_reason, 'gia hạn')) {
            return redirect()->back()->with('error', 'Bạn đã gửi một yêu cầu gia hạn hợp đồng trước đó. Vui lòng chờ chủ trọ phê duyệt!');
        }
        $request->validate([
            'desired_months' => 'required|integer|min:1|max:36',
            'note' => 'nullable|string|max:1000',
        ], [
            'desired_months.required' => 'Vui lòng chọn số tháng muốn gia hạn.',
            'desired_months.min' => 'Số tháng gia hạn tối thiểu là 1 tháng.',
        ]);
        $months = (int) $request->input('desired_months');
        $note = trim($request->input('note', ''));
        //tự động tính ngày hết hạn đề xuất mới dựa trên số tháng khách chọn
        $currentEndDate = $contract->end_date ? \Carbon\Carbon::parse($contract->end_date) : now();
        $suggestedEndDate = $currentEndDate->addMonths($months)->format('Y-m-d');
        $requestedInfor = "Khách xin gia hạn thêm {$months} tháng (Ngày kết thúc đề xuất: " . date('d/m/Y', strtotime($suggestedEndDate)) . ")" . ($note ? ". Ghi chú: {$note}" : "");
        //lưu thông tin đề xuất vào hợp đồng để chủ trọ nạp tự động
        \App\Models\Contract::$allowImmutableUpdate = true;
        $contract->update([
            'cancellation_reason' => $requestedInfor
        ]);

        \App\Models\Contract::$allowImmutableUpdate = false;
        //gửi thông báo tới chủ trọ
        $landlord = $contract->room->boardingHouse->user ?? null;
        if ($landlord) {
            $roomNum = $contract->room->room_number ?? "";
            $landlord->notify(new \App\Notifications\AdminNotification(
                'Yêu cầu gia hạn hợp đồng mới',
                "Khách thuê tại phòng {$roomNum} ({$user->name}) gửi yêu cầu gia hạn {$months} tháng. {$requestedInfor}",
                'info',
                route('landlord.contracts')
            ));
        }
        return redirect()->route('quanlynoio')->with('success', "Đã gửi yêu cầu gia hạn hợp đồng ({$months} tháng) thành công tới chủ trọ!");
    }

    /**
     * Cập nhật chỉ số điện/nước ban đầu khi nhận phòng
     */
    public function submitEntryReadings(Request $request, $contractId)
    {
        $disk = (config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret')) ? 'r2_public' : 'public';
        $request->validate([
            'entry_elec_index' => 'required|integer|min:0',
            'entry_water_index' => 'required|integer|min:0',
            'entry_elec_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'entry_water_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $contract = \App\Models\Contract::where('id', $contractId)
            ->where('tenant_id', $request->user()->id)
            ->firstOrFail();

        $elecImgPath = $contract->entry_elec_image;
        $waterImgPath = $contract->entry_water_image;

        if ($request->hasFile('entry_elec_image')) {
            $elecImgPath = '/storage/' . $request->file('entry_elec_image')->store('meter_readings/entry', $disk);
        }

        if ($request->hasFile('entry_water_image')) {
            $waterImgPath = '/storage/' . $request->file('entry_water_image')->store('meter_readings/entry', $disk);
        }

        $contract->update([
            'entry_elec_index' => $request->entry_elec_index,
            'entry_elec_image' => $elecImgPath,
            'entry_water_index' => $request->entry_water_index,
            'entry_water_image' => $waterImgPath,
            'entry_readings_submitted_at' => now(),
        ]);

        // Gửi thông báo cho Chủ trọ
        $landlord = $contract->room->boardingHouse->user ?? null;
        if ($landlord) {
            $roomNum = $contract->room->room_number ?? '';
            $landlord->notify(new \App\Notifications\AdminNotification(
                'Khách đã cập nhật chỉ số nhận phòng',
                "Khách thuê tại phòng {$roomNum} đã tải lên chỉ số điện/nước ban đầu lúc nhận phòng.",
                'info',
                route('landlord.contracts')
            ));
        }

        return Redirect::back()->with('success', 'Đã cập nhật chỉ số điện/nước nhận phòng thành công!');
    }

    //gửi yêu cầu tìm người lạ ở ghép
    public function requestStrangerRoommate(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->profileService->createStrangerRequest($request->user());
            return redirect()->back()->with('success', 'Gửi yêu cầu tìm người ở ghép thành công! Vui lòng chờ chủ trọ phê duyệt.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    //gửi yêu cầu giới thiệu người quen vào ở ghép
    public function requestAcquaintanceRoommate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'new_resident_name' => 'required|string|max:255',
            'new_resident_phone' => 'required|string|max:15',
            'new_resident_email' => 'required|email|max:255',
            'new_resident_cccd' => 'required|string|regex:/^\d{12}$/', // CCCD 12 số
        ], [
            'new_resident_name.required' => 'Họ tên người quen là bắt buộc.',
            'new_resident_phone.required' => 'Số điện thoại là bắt buộc.',
            'new_resident_email.required' => 'Email là bắt buộc.',
            'new_resident_cccd.required' => 'Số CCCD là bắt buộc.',
            'new_resident_cccd.regex' => 'Số CCCD bắt buộc phải đúng 12 chữ số.'
        ]);
        try {
            $this->profileService->createAcquaintanceRequest($request->user(), $request->only([
                'new_resident_name',
                'new_resident_phone',
                'new_resident_email',
                'new_resident_cccd',
            ]));
            return redirect()->back()->with('success', 'Gửi thông báo giới thiệu thành viên mới thành công! Vui lòng chờ chủ trọ duyệt.');
        } catch (\Exception $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'new_resident_email' => $e->getMessage(),
            ]);
        }
    }

    //hàm nhận token từ điện thoại
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string',]);
        $user = auth()->user();
        if ($user) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }
        return response()->json(['messeage' => 'Cập nhật FCM Token thành công!']);
    }

    //hàm hiển thị trang cài đặt
    public function settings(Request $request)
    {
        $sessions = [];
        if (config('session.driver') === 'database') {
            $sessions = DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->orderBy('last_activity', 'desc')
                ->get()
                ->map(function ($session) use ($request) {
                    $agent = $this->parseUserAgent($session->user_agent);
                    return [
                        'id' => $session->id,
                        'device' => $agent['device'],
                        'platform' => $agent['platform'],
                        'browser' => $agent['browser'],
                        'ip_address' => $session->ip_address,
                        'is_current_device' => $session->id === $request->session()->getId(),
                        'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    ];
                });
        }
        return Inertia::render('Profile/caidat', [
            'sessions' => $sessions,
        ]);
    }
    //hàm phân tích thông tin trình duyệt & thiết bị từ user
    private function parseUserAgent($ua)
    {
        $browser = 'Trình duyệt';
        if (str_contains($ua, 'Chrome'))
            $browser = 'Chrome';
        elseif (str_contains($ua, 'Safari'))
            $browser = 'Safari';
        elseif (str_contains($ua, 'Firefox'))
            $browser = 'Firefox';
        elseif (str_contains($ua, 'Edge'))
            $browser = 'Edge';
        $platform = 'Máy tính';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))
            $platform = 'iOS';
        elseif (str_contains($ua, 'Android'))
            $platform = 'Android';
        elseif (str_contains($ua, 'Windows'))
            $platform = 'Windows';
        elseif (str_contains($ua, 'Macintosh'))
            $platform = 'Mac';
        return [
            'device' => (str_contains($ua, 'Mobile') ? 'Điện thoại' : 'Máy tính'),
            'platform' => $platform,
            'browser' => $browser,
        ];
    }
    //hàm xoá tài khoản (yêu cầu xác nhận mật khẩu hiện tại)
    public function destroyAccount(Request $request)
    {
        //xác minh mật khẩu hiện tại
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu hiện tại để xác nhận',
            'password.current_password' => 'Mật khẩu xác nhận không chính xác',
        ]);
        $userId = $request->user()->id;
        //check hợp đồng nếu clien đang có hợp đồng hiệu lực
        $hasActiveContract = Contract::where('tenant_id', $userId)
            ->whereIn('status', ['awaiting_upload', 'signed', 'active', 'expiring', 'termination_requested'])
            ->exists();
        //check người dùng là thành viên ở ghép đang hoạt động
        $hasActiveResidency = RoomResident::where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
        if ($hasActiveContract || $hasActiveResidency) {
            throw ValidationException::withMessages([
                'password' => 'Tài khoản của bạn hiện đang có hợp đồng hoặc phòng trọ còn hiệu lực! Bạn không thể xóa tài khoản. Vui lòng thanh lý hợp đồng và trả phòng trước khi xóa.',
            ]);
        }
        //đăng xuất người dùng
        Auth::logout();
        //xoá tài khoản khỏi database
        $user = $request->user();
        Auth::logout();
        $user ->delete();
        //huỷ session
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Tài khoản của bạn đã được xóa thành công.');
    }
}


