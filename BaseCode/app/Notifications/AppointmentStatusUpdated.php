<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Appointment;
use App\Channels\FcmChannel;

class AppointmentStatusUpdated extends Notification
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
        return ['database', FcmChannel::class];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $landlordName = $this->appointment->landlord ? $this->appointment->landlord->name : 'Chủ trọ';
        $roomNumber = $this->appointment->room ? $this->appointment->room->room_number : '';
        $dateStr = date('d/m/Y', strtotime($this->appointment->date));
        $timeStr = date('H:i', strtotime($this->appointment->time));
        $statusText = $this->appointment->status === 'approved' ? 'phê duyệt' : 'từ chối';

        return [
            'title' => 'Cập nhật trạng thái lịch hẹn',
            'message' => "Lịch hẹn xem {$roomNumber} vào ngày {$dateStr} lúc {$timeStr} đã được {$landlordName} {$statusText}.",
            'url' => route('profile.appointments'),
        ];
    }
}
