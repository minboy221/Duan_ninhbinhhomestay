<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

use App\Services\AuthService;

class SocialiteController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback()
    {
        try {
            $this->authService->handleGoogleLogin();

            // Redirect to home or dashboard
            if (auth()->check() && auth()->user()->role === 'landlord') {
                return redirect()->route('landlord.dashboard');
            }
            return redirect('/');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Login Error: ' . $e->getMessage());
            // Handle error, e.g., user denied access
            return redirect('/login')->withErrors(['email' => 'Đăng nhập bằng Google thất bại. Vui lòng thử lại. (' . $e->getMessage() . ')']);
        }
    }
}
