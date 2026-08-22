<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatHistory extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_histories';

    protected $fillable = [
        'user_id',
        'sender',
        'message',
        'rooms_data',
        'ai_parsed',
        'suggestions',
    ];

    protected $casts = [
        'rooms_data' => 'array',
        'ai_parsed' => 'array',
        'suggestions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
