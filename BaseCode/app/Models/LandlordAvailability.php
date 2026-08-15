<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandlordAvailability extends Model
{
    protected $fillable = [
        'landlord_id',
        'boarding_house_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];
}
