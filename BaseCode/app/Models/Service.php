<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'amenity_id',
        'name',
        'price',
        'price_updated_at',
        'type',
        'description',
        'icon',
        'color',
        'is_active',
        'boarding_house_id',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'price_updated_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }

    public function boardingHouse()
    {
        return $this->belongsTo(BoardingHouse::class, 'boarding_house_id');
    }
}
