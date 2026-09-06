<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProfileUnlockRejectedNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Yêu cầu mở khóa hồ sơ bị từ chối',
            'message' => 'Yêu cầu xin mở khóa chỉnh sửa thông tin cá nhân của bạn đã bị Admin từ chối.',
            'url'     => route('tranguser'),
            'type'    => 'profile_unlock_rejected',
        ];
    }
}


?>