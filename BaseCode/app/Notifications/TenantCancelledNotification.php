<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantCancelledNotification extends Notification
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
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $tenantName = $this->appointment->user?->name ?? 'Khách hàng';
        $roomNumber = $this->appointment->room?->room_number ?? '';
        $reason = $this->appointment->cancellation_reason ?? 'Không có lý do';
        return [
            'title' => 'Khách hàng đổi ý thuê phòng',
            'message' => "Khách hàng {$tenantName} đã huỷ đăng ký thuê phòng {$roomNumber}. Lý do: {$reason}",
            'appointment_id' => $this->appointment->id,
            'type' => 'tenant_cancelled',
        ];
    }
}
