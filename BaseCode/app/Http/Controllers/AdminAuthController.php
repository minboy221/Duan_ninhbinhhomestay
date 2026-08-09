<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login view.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request for admin.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Check rate limits
        $request->ensureIsNotRateLimited();

        // Attempt login using Auth facade directly
        // Removing captcha as seen in AuthService if it were there, but LoginRequest standard only validates email and password.
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::hit($request->throttleKey());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }

        // Check if the user is actually an admin
        if (Auth::user()->role !== 'admin') {
            // Not an admin, log them out
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            \Illuminate\Support\Facades\RateLimiter::hit($request->throttleKey());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }

        // Check if account is locked
        if (Auth::user()->status === 'locked') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        // Authentication passed and user is admin
        \Illuminate\Support\Facades\RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
