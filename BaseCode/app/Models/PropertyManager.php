<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyManager extends Model
{
    use HasFactory;
    protected $appends = ['hash_id'];
    protected $table = 'property_mangagers';
    protected $fillable = [
        'boarding_house_id',
        'user_id',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    //mối quan hệ tới tài khoản người dùng được phân quyền
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    //mối quan hệ tới nhà trọ cơ sở trọ
    public function boardingHouse(){
        return $this->belongsTo(BoardingHouse::class,'boarding_house_id');
    }
}
