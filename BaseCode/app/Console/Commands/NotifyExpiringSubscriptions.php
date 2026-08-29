<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LandlordSubscription;
use App\Notifications\SubscriptionNotification;
use Carbon\Carbon;

class NotifyExpiringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:notify-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động gửi thông báo cho chủ trọ khi gói dịch vụ còn 3 ngày là hết hạn';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Lấy ngày mục tiêu là đúng 3 ngày sau
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $expiringSubs = LandlordSubscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', $targetDate)
            ->get();

        foreach ($expiringSubs as $sub) {
            if ($sub->user && $sub->plan) {
                $endDateStr = Carbon::parse($sub->end_date)->format('d/m/Y');
                $sub->user->notify(new SubscriptionNotification(
                    'Gói dịch vụ sắp hết hạn!',
                    "Gói \"{$sub->plan->name}\" của bạn sẽ hết hạn sau 3 ngày nữa (vào ngày {$endDateStr}). Vui lòng gia hạn để duy trì dịch vụ.",
                    route('landlord.subscriptions.index'),
                    'warning'
                ));
            }
        }

        $this->info("Đã gửi thông báo sắp hết hạn cho " . $expiringSubs->count() . " gói dịch vụ.");
    }
}