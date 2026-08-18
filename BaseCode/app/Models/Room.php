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
        'property_id',
        'floor_id',
        'room_number',
        'address',
        'price',
        'area',
        'capacity',
        'current_people',
        'status',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    protected $appends = [
        'current_people',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'room_id');
    }

    //phần thêm người ở ghép
    public function residents(){
        return $this->hasMany(RoomResident::class,'room_id')->where('status','active');
    }

    public function getCurrentPeopleAttribute()
    {
        $dbValue = (int) ($this->attributes['current_people'] ?? 0);
        $activeContractsSum = (int) $this->contracts()
            ->whereIn('status', ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'])
            ->sum('number_of_tenants');
            
        $base = max($dbValue, $activeContractsSum);
        
        if (in_array($this->attributes['status'] ?? '', ['rented', 'deposited'])) {
            return max($base, 1);
        }
        
        return $base;
    }

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


    // quan hệ phòng thuộc về một boarding house / property
    public function property()
    {
        return $this->belongsTo(BoardingHouse::class, 'boarding_house_id');
    }

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
        return $this->belongsToMany(Service::class)->withPivot('price');
    }

    //quan hệ một phòng có nhiều bài đăng tiếp thị
    public function roomPosts()
    {
        return $this->hasMany(RoomPost::class, 'room_id');
    }
}
