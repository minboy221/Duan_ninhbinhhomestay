<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'landlord_id',
        'name',
        'description',
        'address',
        'city',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'property_id');
    }

    public function floors()
    {
        return $this->hasMany(Floor::class)->orderBy('sort_order');
    }

     //phần nhận báo cáo
    public function reports(){
        return $this->morphMany(\App\Models\Report::class,'reportable');
    }
}
