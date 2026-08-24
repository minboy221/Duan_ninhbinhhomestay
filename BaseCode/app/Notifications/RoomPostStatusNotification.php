<?php

namespace App\Notifications;

use App\Models\RoomPost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel;

class RoomPostStatusNotification extends Notification
{
    use Queueable;
    protected $post;
    protected $action;

    /**
     * Hàm khởi tạo nhận vào bài đăng và hành động của Admin
     */
    public function __construct(RoomPost $post, string $action)
    {
        $this->post = $post;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */

    //Khai báo kênh thông báo: lưu vào bảng notifications
    public function via($notifiable): array
    {
        return ['database', FcmChannel::class];
    }
    /**
     * Cấu hình mảng dữ liệu: Laravel sẽ tự động ép mảng này thành chuỗi JSON 
     * và nhét vào cột `data` trong bảng notifications của bạn.
     */
    public function toArray($notifiable): array
    {
        if ($this->action === 'approved') {
            return [
                'title' => 'Tin đăng của bạn đã được duyệt',
                'message' => "Bài đăng \"{$this->post->title}\" của bạn đã được phê duyệt thành công và được hiển thị công khai",
                'type' => 'listing_approved',
                'post_id' => $this->post->id,
                'url' => route('landlord.listings.index')
            ];
        }
        //trường hợp bị từ chối
        return [
            'title' => 'Tin đăng của bạn bị từ chối',
            'message' => "Bài đăng \"{$this->post->title}\" của bạn bị từ chối. Lý do: {$this->post->reject_reason}",
            'type' => 'listing_rejected',
            'post_id' => $this->post->id,
            'url' => route('landlord.listings.edit', $this->post->id)
        ];
    }
}
