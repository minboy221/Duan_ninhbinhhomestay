<?php

namespace App\Notifications;

use App\Models\RoomPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRoomPostNotification extends Notification
{
    use Queueable;
    protected $post;

    public function __construct(RoomPost $post)
    {
        $this->post = $post;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Yêu cầu duyệt tin đăng mới',
            'message' => "Chủ trọ {$this->post->landlord->name} vừa gửi yêu cầu duyệt tin đăng: \"{$this->post->title}\"",
            'type' => 'new_room_post',
            'post_id' => $this->post->id,
            'url' => route('admin.listings.index')
        ];
    }
}
