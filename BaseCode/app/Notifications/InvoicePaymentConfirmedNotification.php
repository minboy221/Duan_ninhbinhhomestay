<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;
use App\Channels\FcmChannel;

class InvoicePaymentConfirmedNotification extends Notification
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
        $roomNumber = $this->invoice->contract->room->room_number ?? 'Phòng';
        $billingMonth = $this->invoice->billing_month;
        $paidAmount = (float) ($this->invoice->paid_amount ?: 0);
        $totalAmount = (float) $this->invoice->total_amount;

        $statusText = $this->invoice->status === 'paid' ? 'Đã thanh toán đủ' : 'Thanh toán một phần';
        $amountStr = number_format($paidAmount, 0, ',', '.') . ' VNĐ';

        return [
            'title' => 'Xác nhận thanh toán hóa đơn',
            'message' => "Hóa đơn kỳ {$billingMonth} (Phòng {$roomNumber}) đã được xác nhận thanh toán ({$statusText}: {$amountStr}).",
            'url' => route('lichsuthanhtoan'),
            'invoice_id' => $this->invoice->id,
            'type' => 'success',
        ];
    }
}
