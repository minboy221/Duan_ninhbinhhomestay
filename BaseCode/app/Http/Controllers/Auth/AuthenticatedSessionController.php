<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\AuthService;

class AuthenticatedSessionController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Check rate limits and throw exception if necessary
        $request->ensureIsNotRateLimited();

        if (!$this->authService->loginAccount($request->only('email', 'password'), $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::hit($request->throttleKey());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }
        
        // Prevent admins from logging in here
        if (Auth::user()->role === 'admin') {
            $this->authService->logoutAccount();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            \Illuminate\Support\Facades\RateLimiter::hit($request->throttleKey());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }

        // Check if account is locked
        if (Auth::user()->status === 'locked') {
            $this->authService->logoutAccount();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($request->throttleKey());

        $request->session()->regenerate();

        $user = $request->user();
        $request->session()->forget('url.intended');

        // chuyển hướng chính xác theo vai trò role của tài khoản
        if ($user && $user->role === 'landlord') {
            return redirect()->route('landlord.dashboard');
        }
        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        // khách thuê / người dùng
        return redirect()->route('home');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logoutAccount();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
