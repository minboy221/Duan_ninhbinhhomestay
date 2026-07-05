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
        return Inertia::render('Profile/qlynoio', [
            'user' => $request->user(),
        ]);
    }

    //trang thanh toán
    public function lichsuthanhtoan(Request $request): Response
    {
        return Inertia::render('Profile/listthanhtoan', [
            'user' => $request->user(),
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
}
