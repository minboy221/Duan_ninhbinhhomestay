<?php
namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FcmService;
class FcmChannel
{
    //tự động gửi thông báo firebase tới điện thoại mỗi khi có thông báo
    public function send($notifiable, Notification $notification){
        //nếu người dùng chưa bận hoặc chưa lưu fcm_token thì bỏ qua
        if(empty($notifiable->fcm_token)){
            return;
        }
        //tự động trích xuát tiêu đề, nội dung
        $data = [];
        if(method_exists($notification, 'toArray')){
            $data = $notification->toArray($notifiable);
        }
        $title = $data['title'] ?? 'Thông báo từ hệ thống';
        $body = $data['message'] ?? $data['body'] ?? '';
        $url = $data['url'] ?? '/';
        //bắn push thông báo tới thiết bị điện thoại qua firebase
        if(!empty($body)){
            FcmService::sendPushNotification(
                $notifiable->fcm_token,
                $title,
                $body,
                $url,
            );
        }
    }
}

?>