<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;

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
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $billingMonth = $this->invoice->billing_month;
        $amountStr = number_format($this->invoice->total_amount, 0, ',', '.') . ' đ';

        return [
            'title' => 'Bạn có hóa đơn mới',
            'message' => "Hóa đơn tháng {$billingMonth} của phòng bạn đã được tạo với tổng số tiền là {$amountStr}. Hạn thanh toán: " . ($this->invoice->due_date ? $this->invoice->due_date->format('d/m/Y') : ''),
            'url' => route('lichsuthanhtoan'),
            'invoice_id' => $this->invoice->id,
        ];
    }
}
