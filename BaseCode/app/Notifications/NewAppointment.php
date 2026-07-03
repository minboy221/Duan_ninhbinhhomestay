<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Appointment;

class NewAppointment extends Notification
{
    use Queueable;

    protected $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $tenantName = $this->appointment->user ? $this->appointment->user->name : 'Khách hàng';
        $roomNumber = $this->appointment->room ? $this->appointment->room->room_number : '';
        $dateStr = date('d/m/Y', strtotime($this->appointment->date));
        $timeStr = date('H:i', strtotime($this->appointment->time));

        return [
            'title' => 'Yêu cầu đặt lịch xem phòng',
            'message' => "Khách hàng {$tenantName} đã đặt lịch xem {$roomNumber} vào ngày {$dateStr} lúc {$timeStr}.",
            'url' => route('landlord.appointments'),
        ];
    }
}
