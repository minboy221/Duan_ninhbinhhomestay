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

class PasswordController extends Controller
{
    /**
     * Request OTP before updating password
     */
    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        
        // Generate a 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Save to database with 15 minutes expiration
        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(15);
        $user->save();

        // Send Email
        Mail::to($user->email)->send(new ChangePasswordOTP($otp));

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

        $user = $request->user();

        // Verify OTP
        if (!$user->otp_code || $user->otp_code !== $validated['otp'] || Carbon::now()->greaterThan($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'Mã OTP không hợp lệ hoặc đã hết hạn.']);
        }

        // Update password and clear OTP
        $user->update([
            'password' => Hash::make($validated['password']),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return back();
    }
}
