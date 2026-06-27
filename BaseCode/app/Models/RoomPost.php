<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomPost extends Model
{
    use HasFactory;
    protected $table = 'room_posts';

    protected $fillable = [
        'landlord_id',
        'room_id',
        'title',
        'description',
        'image',
        'status',
        'reject_reason',
        'view_count',
        'is_vip',
        'published_at'
    ];

    protected $casts = [
        'image' => 'array',
        'array',
        'published_at' => 'datetime',
    ];

    public function landlord()
    {
        return $this->belongsTo(User::class, 'laldlord', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
