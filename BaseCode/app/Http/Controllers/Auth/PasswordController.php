<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChangePasswordOTP;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Services\AuthService;

class PasswordController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Request OTP before updating password
     */
    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $this->authService->requestPasswordOtp($request->user());

        return back()->with('status', 'otp-sent');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $success = $this->authService->updatePassword($request->user(), $validated['password'], $validated['otp']);

        if (!$success) {
            return back()->withErrors(['otp' => 'Mã OTP không hợp lệ hoặc đã hết hạn.']);
        }

        return back()->with('status', 'password-updated');
    }
}
