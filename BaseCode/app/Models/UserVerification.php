<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'id_card_number',
        'id_card_front',
        'id_card_back',
        'face_auth_image',
        'kyc_status',
        'kyc_notes',
    ];
    //mối quan hệ n-1 với bảng users
    public function user(){
        return $this->belongsTo(User::class);
    }
}
