<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Kiểm tra user có tồn tại chưa hoặc tạo mới
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
            ]
        );

        Auth::login($user);
        if (Auth::check() && Auth::user()->role === 'landlord') {
            return redirect()->route('landlord.dashboard');
        }
        return redirect('/')->with('success', 'Đăng nhập thành công');
    }
}
