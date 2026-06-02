<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'floor_id',
        'room_number',
        'address',
        'price',
        'area',
        'capacity',
        'status',
        'amenities',
        'images',
    ];

    protected $casts = [
        'price'  => 'decimal:2',
        'area'   => 'decimal:2',
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

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }
}
