<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
class BoardingHouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'district',
        'address_detail',
        'contract_images',
        'room_images',
        'status',
        'latitude',
        'longitude',
        'cancel_after_minutes',
        'directions_guide',
    ];
    protected $casts = [
        'contract_images' => 'array',
        'room_images' => 'array',
    ];

    protected $appends = ['average_rating'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function floors()
    {
        return $this->hasManyThrough(
            Floor::class,
            Property::class,
            'landlord_id',   // Khóa ngoại trên bảng properties trỏ tới user_id của chủ trọ
            'property_id',   // Khóa ngoại trên bảng floors trỏ tới id của properties
            'user_id',       // Khóa nội bộ trên bảng boarding_houses lưu ID chủ trọ
            'id'             // Khóa nội bộ trên bảng properties
        );
    }

    public function landlord()
    {
        // Liên kết bảng BoardingHouse với bảng Users qua khoá ngoại user_id
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'boarding_house_id');
    }

    public function reviews()
    {
        return $this->hasManyThrough(Review::class, Room::class, 'boarding_house_id', 'room_id');
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->count() > 0 ? round($this->reviews()->avg('rating'), 1) : 0;
    }
}
