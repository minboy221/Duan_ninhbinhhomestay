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
use Inertia\Inertia;
use Inertia\Response;

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
        $contract = \App\Models\Contract::where('tenant_id', $request->user()->id)
            ->whereIn('status', ['awaiting_upload', 'signed', 'active'])
            ->with(['room.boardingHouse.user', 'invoices'])
            ->orderBy('created_at', 'desc')
            ->first();

        return Inertia::render('Profile/qlynoio', [
            'user' => $request->user(),
            'contract' => $contract,
        ]);
    }

    //trang thanh toán
    public function lichsuthanhtoan(Request $request): Response
    {
        $invoices = \App\Models\Invoice::whereHas('contract', function ($q) use ($request) {
            $q->where('tenant_id', $request->user()->id);
        })
            ->with(['details.service', 'contract.room.boardingHouse.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $reasons = \App\Models\ReportReason::where('is_active', true)->get();

        return Inertia::render('Profile/listthanhtoan', [
            'user' => $request->user(),
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
        $appointments = \App\Models\Appointment::with(['room.boardingHouse.landlord'])
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

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

        if ($request->result === 'interested') {
            $landlord = $appointment->room->boardingHouse->user ?? null;
            if ($landlord) {
                $landlord->notify(new \App\Notifications\TenantInterestedNotification($appointment));
            }
        }

        return Redirect::back()->with('success', 'Đã ghi nhận lựa chọn của bạn!');
    }

    /**
     * Gửi thông báo đã thanh toán cho chủ trọ
     */
    public function notifyPayment(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string|in:qr,cash'
        ]);

        $invoice = \App\Models\Invoice::where('id', $id)
            ->whereHas('contract', function ($q) use ($request) {
                $q->where('tenant_id', $request->user()->id);
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

        return Redirect::back()->with('success', 'Đã gửi thông báo đã chuyển khoản thành công tới Chủ trọ!');
    }
}
