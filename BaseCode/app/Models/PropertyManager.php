<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyManager extends Model
{
    use HasFactory;

    protected $table = 'property_mangagers';
    protected $fillable = [
        'boarding_house_id',
        'user_id',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
    ];
}
