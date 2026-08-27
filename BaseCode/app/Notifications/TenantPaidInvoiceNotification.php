<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;
use App\Channels\FcmChannel;

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
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $roomNumber = $this->invoice->contract->room->room_number ?? 'Phòng';
        $billingMonth = $this->invoice->billing_month;
        $totalAmount = (float) $this->invoice->total_amount;
        $paidAmount = (float) ($this->invoice->paid_amount ?: 0);

        if ($paidAmount > 0 && $paidAmount < $totalAmount) {
            $amountStr = number_format($paidAmount, 0, ',', '.') . ' đ / ' . number_format($totalAmount, 0, ',', '.') . ' đ (Thanh toán 1 phần)';
        } else {
            $amountStr = number_format($totalAmount, 0, ',', '.') . ' đ';
        }

        return [
            'title' => 'Thông báo thanh toán hóa đơn',
            'message' => "Khách thuê tại {$roomNumber} đã báo thanh toán hóa đơn kỳ {$billingMonth} (Số tiền: {$amountStr}) bằng phương thức {$this->paymentMethod}.",
            'url' => route('landlord.invoices'),
            'invoice_id' => $this->invoice->id,
        ];
    }
}
