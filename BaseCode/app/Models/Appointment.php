<?php

namespace App\Models;

use Aws\HasDataTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
class Appointment extends Model
{
    use HasFactory, Hashidable;

    protected $fillable = [
        'user_id',
        'landlord_id',
        'room_id',
        'date',
        'time',
        'note',
        'status',
        'notified',
        'feedback_result',
        'feedback_reason',
        'feedback_time',
        'cancellation_reason',
    ];

    protected $casts = [
        'notified' => 'boolean',
    ];

    protected $appends = ['hash_id'];

    /**
     * Relationship to the tenant (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship to the landlord (User)
     */
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    /**
     * Relationship to the room
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
