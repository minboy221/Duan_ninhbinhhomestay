<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }

    /**
     * Mark the authenticated user's email address as verified using OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($user->otp_code !== $request->otp) {
            return back()->withErrors(['otp' => 'Mã xác nhận không đúng.']);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'Mã xác nhận đã hết hạn. Vui lòng yêu cầu gửi lại mã.']);
        }

        if ($user->markEmailAsVerified()) {
            $user->forceFill([
                'otp_code' => null,
                'otp_expires_at' => null,
            ])->save();
            
            event(new Verified($user));
        }

        if ($user->role === 'landlord') {
            return redirect()->route('landlord.dashboard')->with('success', 'Email của bạn đã được xác minh thành công!');
        }

        return redirect()->intended(RouteServiceProvider::HOME . '?verified=1')->with('success', 'Email của bạn đã được xác minh thành công!');
    }
}
