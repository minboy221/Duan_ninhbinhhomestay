<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueInvoiceNotification extends Notification
{
    use Queueable;
    protected $invoice;
    /**
     * Create a new notification instance.
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('⚠️ CẢNH BÁO: Hóa đơn tiền nhà đã quá hạn thanh toán!')
            ->line("Kính gửi {$notifiable->name},")
            ->line("Hóa đơn mã #{$this->invoice->invoice_code} (Kỳ tháng {$this->invoice->billing_month}) của phòng {$this->invoice->contract->room->room_number} đã quá hạn đóng vào ngày " . date('d/m/Y', strtotime($this->invoice->due_date)) . ".")
            ->line("Số tiền còn thiếu: " . number_format($this->invoice->total_amount - ($this->invoice->paid_amount ?? 0)) . " VNĐ.")
            ->action('Xem & Thanh Toán Ngay', url('/profile/listthanhtoan'))
            ->line('Vui lòng thanh toán sớm để tránh gián đoạn dịch vụ.');
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'title' => '⚠️ Cảnh báo quá hạn thanh toán',
            'message' => "Hóa đơn #{$this->invoice->invoice_code} cho kỳ tháng {$this->invoice->billing_month} đã quá hạn. Vui lòng thanh toán!",
            'invoice_id' => $this->invoice->id,
            'type' => 'invoice_overdue',
        ];
    }
}
