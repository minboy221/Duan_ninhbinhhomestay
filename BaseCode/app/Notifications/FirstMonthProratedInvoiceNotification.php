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
        $totalAmount = (float) $this->invoice->total_amount;
        $amountStr = number_format($totalAmount, 0, ',', '.') . ' đ';

        $room = $this->invoice->contract->room ?? null;
        $peopleCount = max(1, (int) ($room->current_people ?? 1));

        $message = "Chào mừng bạn! Hóa đơn tháng {$billingMonth} của phòng bạn được tính theo số ngày ở thực tế ({$this->daysOccupied} ngày). Tổng thanh toán: {$amountStr}.";
        if ($peopleCount > 1) {
            $perPersonStr = number_format(round($totalAmount / $peopleCount), 0, ',', '.') . ' đ';
            $message .= " (Phòng {$peopleCount} người - Dự kiến trung bình: {$perPersonStr}/người).";
        }
        if ($this->explanation) {
            $message .= " {$this->explanation}";
        }

        return [
            'title' => '📢 Hóa đơn tháng đầu tiên (Tính số ngày lẻ)',
            'message' => $message,
            'url' => route('lichsuthanhtoan'),
            'invoice_id' => $this->invoice->id,
            'is_first_month' => true,
            'days_occupied' => $this->daysOccupied,
        ];
    }
}
