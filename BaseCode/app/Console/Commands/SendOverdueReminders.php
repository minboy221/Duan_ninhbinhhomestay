<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Notifications\OverdueInvoiceNotification;
use Carbon\Carbon;

class SendOverdueReminders extends Command
{
    protected $signature = 'invoices:send-overdue-reminders';
    protected $description = 'Gửi thông báo nhắc nợ tự động cho các hoá đơn quá hạn';
    public function handle()
    {
        // CHỈ LẤY CÁC HÓA ĐƠN CÓ HẠN ĐÓNG LÀ NGÀY HÔM QUA (SÁNG NAY VỪA QUÁ HẠN)
        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->whereDate('due_date', Carbon::yesterday())
            ->whereNull('archived_at')
            ->with(['contract.tenant', 'contract.room.residents.user'])
            ->get();
        $count = 0;
        foreach ($overdueInvoices as $inv) {
            if ($inv->contract?->tenant) {
                $inv->contract->tenant->notify(new OverdueInvoiceNotification($inv));
                $count++;
            }
        }
        $this->info("Đã gửi nhắc nợ tự động cho {$count} khách thuê!");
    }
}


?>