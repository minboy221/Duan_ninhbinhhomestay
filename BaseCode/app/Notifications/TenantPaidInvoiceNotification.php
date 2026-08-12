<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;

class TenantPaidInvoiceNotification extends Notification
{
    use Queueable;

    protected Invoice $invoice;
    protected string $paymentMethod;

    public function __construct(Invoice $invoice, string $paymentMethod = 'QR Code')
    {
        $this->invoice = $invoice;
        $this->paymentMethod = $paymentMethod;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $roomNumber = $this->invoice->contract->room->room_number ?? 'Phòng';
        $billingMonth = $this->invoice->billing_month;
        $amountStr = number_format($this->invoice->total_amount, 0, ',', '.') . ' đ';

        return [
            'title' => 'Thông báo thanh toán hóa đơn',
            'message' => "Khách thuê tại {$roomNumber} đã báo thanh toán hóa đơn kỳ {$billingMonth} số tiền {$amountStr} bằng phương thức {$this->paymentMethod}.",
            'url' => route('landlord.invoices'),
            'invoice_id' => $this->invoice->id,
        ];
    }
}
