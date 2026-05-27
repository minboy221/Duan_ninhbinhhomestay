<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin():bool{
        return $this-> role === 'admin';
    }

    public function isUser():bool{
        return $this-> role === 'user';
    }

    public function isLandlord():bool{
        return $this-> role === 'landlord';
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Send the email verification notification.
     * Overridden to send OTP code instead of default link.
     */
    public function sendEmailVerificationNotification()
    {
        $this->otp_code = (string) random_int(100000, 999999);
        $this->otp_expires_at = now()->addMinutes(15);
        $this->save();

        \Illuminate\Support\Facades\Mail::to($this->email)->send(new \App\Mail\VerifyEmailOTP($this->otp_code));
    }
}
