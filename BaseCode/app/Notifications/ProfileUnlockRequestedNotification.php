<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\User;
use Illuminate\Notifications\Notification;

class ProfileUnlockRequestedNotification extends Notification
{
    use Queueable;

    public $user;
    public $reason;

    public function __construct(User $user, string $reason)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Yêu cầu mở khóa sửa thông tin',
            'message' => "Người dùng {$this->user->name} vừa gửi yêu cầu mở khóa thông tin cá nhân. Lý do: {$this->reason}",
            'url' => route('admin.users'),
            'user_id' => $this->user->id,
            'type' => 'profile_unlock_requested',
        ];
    }
}
