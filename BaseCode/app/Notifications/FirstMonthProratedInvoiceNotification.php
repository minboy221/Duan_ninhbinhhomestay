<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;

class FirstMonthProratedInvoiceNotification extends Notification
{
    use Queueable;

    protected Invoice $invoice;
    protected int $daysOccupied;
    protected string $explanation;

    public function __construct(Invoice $invoice, int $daysOccupied, string $explanation = '')
    {
        $this->invoice = $invoice;
        $this->daysOccupied = $daysOccupied;
        $this->explanation = $explanation;
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
            'title' => '📢 Hóa đơn tháng đầu tiên (Tính số ngày lẻ)',
            'message' => "Chào mừng bạn! Hóa đơn tháng {$billingMonth} của bạn được tính theo số ngày ở thực tế ({$this->daysOccupied} ngày). Tổng thanh toán: {$amountStr}. {$this->explanation}",
            'url' => route('lichsuthanhtoan'),
            'invoice_id' => $this->invoice->id,
            'is_first_month' => true,
            'days_occupied' => $this->daysOccupied,
        ];
    }
}
