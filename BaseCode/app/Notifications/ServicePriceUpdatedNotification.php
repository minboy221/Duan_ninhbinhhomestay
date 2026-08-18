<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Service;

class ServicePriceUpdatedNotification extends Notification
{
    use Queueable;

    protected Service $service;
    protected float $oldPrice;
    protected float $newPrice;
    protected string $boardingHouseName;

    public function __construct(Service $service, float $oldPrice, float $newPrice, string $boardingHouseName = '')
    {
        $this->service = $service;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->boardingHouseName = $boardingHouseName;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $oldPriceStr = number_format($this->oldPrice, 0, ',', '.') . ' đ';
        $newPriceStr = number_format($this->newPrice, 0, ',', '.') . ' đ';
        $serviceName = $this->service->name;
        $houseInfo = $this->boardingHouseName ? " tại {$this->boardingHouseName}" : '';

        return [
            'title' => '📢 Điều chỉnh đơn giá dịch vụ',
            'message' => "Chủ trọ vừa điều chỉnh đơn giá dịch vụ [{$serviceName}]{$houseInfo} từ {$oldPriceStr} ➔ {$newPriceStr}. Mức giá mới sẽ áp dụng từ kỳ hóa đơn tiếp theo.",
            'url' => route('lichsuthanhtoan'),
            'service_id' => $this->service->id,
        ];
    }
}
