<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BumpLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_post_id',
        'user_id',
        'package_name',
        'bumped_at',
    ];

    protected $casts = [
        'bumped_at' => 'datetime',
    ];

    public function roomPost()
    {
        return $this->belongsTo(RoomPost::class, 'room_post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
