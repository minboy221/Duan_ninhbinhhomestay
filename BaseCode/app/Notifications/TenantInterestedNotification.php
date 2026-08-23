<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel;

class TenantInterestedNotification extends Notification
{
    use Queueable;

    protected $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase($notifiable)
    {
        $tenantName = $this->appointment->user->name ?? 'Một khách hàng';
        $roomNumber = $this->appointment->room->room_number ?? '';

        return [
            'title' => 'Khách chốt thuê phòng!',
            'message' => "{$tenantName} đã ƯNG phòng {$roomNumber} và muốn thuê. Nhấn vào đây để tạo hợp đồng.",
            'url' => route('landlord.contracts', ['action' => 'create_contract', 'appointment_id' => $this->appointment->id]),
            'type' => 'success',
        ];
    }
}
