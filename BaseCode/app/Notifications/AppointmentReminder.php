<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Appointment;

class AppointmentReminder extends Notification
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
        $roomNumber = $this->appointment->room ? $this->appointment->room->room_number : '';
        $timeStr = date('H:i', strtotime($this->appointment->time));

        return [
            'title' => 'Lịch hẹn xem phòng hôm nay!',
            'message' => "Hôm nay bạn có lịch hẹn xem {$roomNumber} lúc {$timeStr}. Nhấp để xem sơ đồ đường đi và chỉ đường.",
            'url' => route('profile.appointments'),
        ];
    }
}
