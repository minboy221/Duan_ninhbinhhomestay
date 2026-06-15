<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];
    protected $casts = [
        'contract_images' => 'array',
        'room_images' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
