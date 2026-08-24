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
            $mail->subject('[Ninh Bình StayWork] Thông báo: Tài khoản của bạn đã bị khóa!')
                ->greeting("Kính gửi {$notifiable->name},")
                ->line("Tài khoản của bạn ({$notifiable->email}) đã bị tạm khóa quản trị viên.")
                ->line("**Lý do khóa:** " . ($this->reason ?? 'Vi phạm điều khoản dịch vụ.'))
                ->line("**Tác động:** Nếu bạn là Chủ trọ, toàn bộ tin đăng phòng trọ của bạn đã được tạm ẩn khỏi trang tìm kiếm công khai.")
                ->line("Nếu có bất kỳ thắc mắc hoặc cần khiếu nại mở lại tài khoản, vui lòng phản hồi email này hoặc liên hệ bộ phận hỗ trợ:")
                ->line("**Email Hỗ Trợ:** {$this->contactEmail}")
                ->action('Xem Điều Khoản Dịch Vụ', url('/chitietdieukhoan'));
        } else {
            $mail->subject('[Ninh Bình StayWork] Thông báo: Tài khoản của bạn đã được khôi phục!')
                ->greeting("Kính gửi {$notifiable->name},")
                ->line("Tài khoản của bạn ({$notifiable->email}) đã được Admin mở khóa thành công.")
                ->line("Bạn hiện đã có thể đăng nhập và tiếp tục sử dụng tất cả các dịch vụ trên hệ thống.")
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
