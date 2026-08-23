<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Contract;
use App\Channels\FcmChannel;


class ContractCreatedNotification extends Notification
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
        return ['database', FcmChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Chủ trọ đã tạo hợp đồng',
            'message' => 'Hợp đồng cho phòng ' . ($this->contract->room->room_number ?? 'mới') . ' đã được tạo. Vui lòng kiểm tra trong Quản lý nơi ở.',
            'url' => '/quanlynoio',
            'type' => 'contract_created'
        ];
    }
}
