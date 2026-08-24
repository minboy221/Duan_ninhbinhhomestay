<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;

class TenantProfileUpdated extends Notification
{
    use Queueable;

    protected $tenant;
    protected $oldInfo;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $tenant, array $oldInfo)
    {
        $this->tenant = $tenant;
        $this->oldInfo = $oldInfo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $changes = [];
        if ($this->oldInfo['name'] !== $this->tenant->name) {
            $changes[] = "họ tên từ '" . $this->oldInfo['name'] . "' thành '" . $this->tenant->name . "'";
        }
        if ($this->oldInfo['address'] !== $this->tenant->address) {
            $changes[] = "địa chỉ";
        }
        if ($this->oldInfo['job'] !== $this->tenant->job) {
            $changes[] = "nghề nghiệp";
        }
        if ($this->oldInfo['dob'] !== $this->tenant->dob) {
            $changes[] = "ngày sinh";
        }
        if ($this->oldInfo['gender'] !== $this->tenant->gender) {
            $changes[] = "giới tính";
        }

        $detailChanges = implode(', ', $changes);

        return [
            'title' => 'Khách thuê cập nhật thông tin',
            'message' => "Khách thuê " . $this->tenant->name . " đã cập nhật " . $detailChanges . ".",
            'url' => route('landlord.tenants'),
        ];
    }
}
