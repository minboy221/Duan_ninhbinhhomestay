<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel;


class LandlordApproved extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
            'title' => 'Hồ sơ Chủ trọ đã được duyệt',
            'message' => 'Chúc mừng! Bạn đã chính thức trở thành Chủ trọ trên hệ thống Ninh Bình HomeStay. Bạn có thể truy cập Trang Chủ Trọ ngay bây giờ.',
            'url' => route('landlord.dashboard'),
        ];
    }
}
