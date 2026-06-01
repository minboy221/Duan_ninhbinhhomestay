<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function showRegister() { return view('auth.register'); }
    public function showLogin() { return view('auth.login'); }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|min:6',
            'captcha' => 'required|captcha'
        ]);

        $this->authService->registerAccount($validatedData);
        return redirect('/')->with('success', 'Đăng ký thành công!');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'captcha' => 'required|captcha'
        ]);

        if ($this->authService->loginAccount($credentials)) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'Đăng nhập thành công!');
        }

        return back()->withErrors(['email' => 'Thông tin đăng nhập không đúng.']);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $this->authService->handleGoogleLogin();
            return redirect('/')->with('success', 'Đăng nhập Google thành công!');
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['error' => 'Lỗi đăng nhập Google.']);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logoutAccount();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'Bạn đã đăng xuất.');
    }
}