<?php
namespace App\Events;
use Carbon\Traits\Serialization;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;
    public $roomId;
    public $status;

    public function __construct($roomId, $status)
    {
        $this->roomId = $roomId;
        $this->status = $status;
    }
    public function broadcastOn(): array
    {
        return [
            new Channel('rooms')
        ];
    }
}
?>