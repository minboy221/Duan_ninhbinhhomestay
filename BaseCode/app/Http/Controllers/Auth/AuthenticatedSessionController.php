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

        if (!$this->authService->loginAccount($request->only('email', 'password', 'captcha'), $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::hit($request->throttleKey());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($request->throttleKey());

        $request->session()->regenerate();

        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->role === 'landlord') {
            return redirect()->route('landlord.dashboard');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
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
