<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoommateRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_id',
        'tenant_id',
        'type',
        'status',
        'new_resident_name',
        'new_resident_phone',
        'new_resident_email',
        'new_resident_cccd',
    ];
    //quan hệ với phòng trọ
    public function room(){
        return $this->belongsTo(Room::class);
    }
    //quan hệ với user gửi yêu cầu
    public function tenant(){
        return $this->belongsTo(User::class,'tenant_id');
    }
}
