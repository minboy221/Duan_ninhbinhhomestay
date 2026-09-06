<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusNotification extends Notification
{
    use Queueable;
    protected $status;
    protected $reason;
    protected $contactEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $status, ?string $reason = null, string $contactEmail = 'support@ninhbinhstaywork.vn')
    {
        $this->status = $status;
        $this->reason = $reason;
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = new MailMessage();
        if ($this->status === 'locked') {
            $mail->subject('[Ninh Bình StayWork] Thông báo: Tài khoản của bạn đã bị tạm khóa!')
                ->greeting("Kính gửi {$notifiable->name},")
                ->line("Tài khoản của bạn ({$notifiable->email}) đã bị tạm khóa bởi quản trị viên hệ thống.")
                ->line("**Lý do khóa:** " . ($this->reason ?? 'Vi phạm điều khoản hoặc chính sách hệ thống.'))
                ->line("**Lưu ý:** Nếu bạn là Chủ trọ, toàn bộ tin đăng phòng trọ của bạn đã được tạm ẩn khỏi hệ thống tìm kiếm.")
                ->line("Nếu bạn tin rằng đây là sự nhầm lẫn hoặc muốn giải trình để mở lại tài khoản, vui lòng bấm vào nút bên dưới để gửi yêu cầu khiếu nại tới Ban Quản Trị:")
                ->action('Gửi Khiếu Nại Mở Tài Khoản', url('/contact?type=appeal&email=' . urlencode($notifiable->email)))
                ->line("Email liên hệ hỗ trợ trực tiếp: {$this->contactEmail}");
        } else {
            $mail->subject('[Ninh Bình StayWork] Thông báo: Tài khoản của bạn đã được khôi phục!')
                ->greeting("Kính gửi {$notifiable->name},")
                ->line("Tài khoản của bạn ({$notifiable->email}) đã được Admin mở khóa thành công.")
                ->line("Bạn hiện đã có thể đăng nhập và tiếp tục sử dụng tất cả dịch vụ trên hệ thống.")
                ->action('Đăng Nhập Ngay', url('/login'));
        }
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->status === 'locked' ? 'Tài khoản bị khóa' : 'Tài khoản đã mở khóa',
            'message' => $this->status === 'locked' ? "Lý do: {$this->reason}" : 'Tài khoản của bạn đã được kích hoạt lại.',
            'status' => $this->status,
        ];
    }
}
