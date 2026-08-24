<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Channels\FcmChannel;

class AdminNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $type;
    protected $url;

    public function __construct($title, $message, $type, $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['database','broadcast', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url
        ];
    }

    //định nghĩa dữ liệu phát đi thời gian thực
    public function toBroadcast(object $notifiable):BroadcastMessage{
        return new BroadcastMessage([
            'id' => $this->id,
            'data' => [
                'title' => $this->title,
                'message' => $this->message,
                'type' => $this->type,
                'url' => $this->url
            ],
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
