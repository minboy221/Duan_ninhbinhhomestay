<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'boarding_house_id',
        'floor_id',
        'room_number',
        'address',
        'price',
        'area',
        'capacity',
        'status',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Danh sách trạng thái hợp lệ
     */
    public const STATUSES = [
        'available',
        'rented',
        'maintenance',
        'deposited',
        'expiring_soon',
        'pending_renewal',
        'suspended',
        'under_construction',
    ];


    // quan hệ phòng thuộc về một nhà trọ
    public function boardingHouse()
    {
        return $this->belongsTo(BoardingHouse::class, 'boarding_house_id');
    }

    //quan hệ:phòng thuộc về 1 tầng
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id', 'id');
    }

    //quan hệ một phòng có nhiều dịch vụ
    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    //quan hệ một phòng có nhiều bài đăng tiếp thị
    public function roomPosts()
    {
        return $this->hasMany(RoomPost::class, 'room_id');
    }
}
