<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
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
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find user by google_id
            $user = User::where('google_id', $googleUser->getId())->first();

            if (!$user) {
                // If not found by google_id, check if email exists
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // Link the existing user with the google_id
                    $user->google_id = $googleUser->getId();
                    if (!$user->email_verified_at) {
                        $user->email_verified_at = now();
                    }
                    $user->save();
                } else {
                    // Create a new user
                    $user = User::create([
                        'name' => $googleUser->getName() ?? 'Google User',
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'password' => null, // Password can be null for social logins
                        'role' => 'user',
                    ]);
                    
                    // Mark email as verified since it's from Google
                    $user->email_verified_at = now();
                    $user->save();
                }
            }

            // Log the user in
            Auth::login($user, true); // true = remember

            // Redirect to home or dashboard
            return redirect('/');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Login Error: ' . $e->getMessage());
            // Handle error, e.g., user denied access
            return redirect('/login')->withErrors(['email' => 'Đăng nhập bằng Google thất bại. Vui lòng thử lại. (' . $e->getMessage() . ')']);
        }
    }
}
