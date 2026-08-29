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
        $today = Carbon::now()->startOfDay()->toDateString();
        $targetDate = Carbon::now()->startOfDay()->addDays(3)->toDateString();

        // Lấy tất cả các gói active sắp hết hạn trong vòng 3 ngày tới (từ hôm nay đến 3 ngày sau)
        $expiringSubs = LandlordSubscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $targetDate)
            ->get();

        $count = 0;
        foreach ($expiringSubs as $sub) {
            if ($sub->user && $sub->plan) {
                // Kiểm tra xem người dùng đã nhận thông báo sắp hết hạn chưa
                $alreadyNotified = $sub->user->unreadNotifications()
                    ->where('type', 'App\Notifications\SubscriptionNotification')
                    ->where('data->title', 'Gói Dịch Vụ Sắp Hết Hạn')
                    ->exists();

                if (!$alreadyNotified) {
                    $endDateStr = Carbon::parse($sub->end_date)->format('d/m/Y');
                    $daysLeft = max(0, (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($sub->end_date)->startOfDay(), false));

                    $sub->user->notify(new SubscriptionNotification(
                        'Gói Dịch Vụ Sắp Hết Hạn',
                        "Gói \"{$sub->plan->name}\" của bạn sẽ hết hạn vào ngày {$endDateStr} (Còn {$daysLeft} ngày). Vui lòng gia hạn để duy trì dịch vụ.",
                        route('landlord.subscriptions.index'),
                        'warning'
                    ));
                    $count++;
                }
            }
        }

        $this->info("Đã gửi thông báo sắp hết hạn cho {$count} chủ trọ.");
    }
}