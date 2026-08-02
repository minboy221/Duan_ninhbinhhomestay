<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Contract;

class EntryMeterReadingNotification extends Notification
{
    use Queueable;

    protected $contract;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $roomNumber = $this->contract->room->room_number ?? '';
        return [
            'title' => 'Cập nhật chỉ số điện/nước nhận phòng',
            'message' => "Vui lòng đính kèm chỉ số điện và nước ban đầu khi nhận phòng {$roomNumber} để tính tiền minh bạch.",
            'url' => '/quanlynoio',
            'type' => 'entry_meter_reading_request',
            'contract_id' => $this->contract->id,
        ];
    }
}
