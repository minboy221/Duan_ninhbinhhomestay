<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;use App\Channels\FcmChannel;


class NewInvoiceNotification extends Notification
{
    use Queueable;

    protected Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $billingMonth = $this->invoice->billing_month;
        $totalAmount = (float) $this->invoice->total_amount;
        $amountStr = number_format($totalAmount, 0, ',', '.') . ' đ';

        $room = $this->invoice->contract->room ?? null;
        $peopleCount = max(1, (int) ($room->current_people ?? 1));

        $message = "Hóa đơn tháng {$billingMonth} của phòng bạn đã được tạo với tổng số tiền là {$amountStr}.";
        if ($peopleCount > 1) {
            $perPersonStr = number_format(round($totalAmount / $peopleCount), 0, ',', '.') . ' đ';
            $message .= " (Phòng {$peopleCount} người - Dự kiến trung bình: {$perPersonStr}/người).";
        }
        if ($this->invoice->due_date) {
            $message .= " Hạn thanh toán: " . $this->invoice->due_date->format('d/m/Y');
        }

        return [
            'title' => 'Bạn có hóa đơn mới',
            'message' => $message,
            'url' => route('lichsuthanhtoan'),
            'invoice_id' => $this->invoice->id,
        ];
    }
}
