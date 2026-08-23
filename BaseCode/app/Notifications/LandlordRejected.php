<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel;

class LandlordRejected extends Notification
{
    use Queueable;

    protected $reason;

    public function __construct($reason = null)
    {
        $this->reason = $reason ?? 'Hồ sơ không đáp ứng đủ yêu cầu của hệ thống.';
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Hồ sơ Chủ trọ bị từ chối',
            'message' => 'Rất tiếc, hồ sơ đăng ký Chủ trọ của bạn đã bị từ chối. Lý do: ' . $this->reason,
            'url' => '/', // Dẫn về trang chủ hoặc trang cá nhân
        ];
    }
}
