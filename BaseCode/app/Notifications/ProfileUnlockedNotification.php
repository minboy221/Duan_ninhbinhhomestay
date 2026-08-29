<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProfileUnlockedNotification extends Notification
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
            'title' => 'Hồ sơ đã được mở khóa chỉnh sửa',
            'message' => 'Yêu cầu mở khóa chỉnh sửa thông tin cá nhân của bạn đã được Admin phê duyệt. Bạn có thể cập nhật lại hồ sơ ngay bây giờ!',
            'url' => route('tranguser'),
            'type' => 'profile_unlocked',
        ];
    }
}
