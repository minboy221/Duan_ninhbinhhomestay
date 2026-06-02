<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;

class NewLandlordApplication extends Notification
{
    use Queueable;

    protected $applicant;

    public function __construct(User $applicant)
    {
        $this->applicant = $applicant;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Có hồ sơ đăng ký Chủ trọ mới',
            'message' => 'Người dùng ' . $this->applicant->name . ' vừa gửi yêu cầu xét duyệt Chủ trọ. Vui lòng kiểm tra và phê duyệt.',
            'url' => route('admin.verifications.show', $this->applicant->id),
        ];
    }
}
