<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AdminNotification;

class CheckExpiredReports extends Command
{
    //tạo lệnh để gọi trong terminal
    protected $signature = 'reports:check-expired';
    //mô tả lệnh
    protected $description = 'Quét các báo cáo qua hạn thương lượng để tự động gửi thông báo cho chủ trọ, khách thuê và Admin';
    public function handle()
    {
        //tìm các báo cáo dang thương lượng, chủ trọ chưa giải quyết và đã quá hạn
        $expiredReports = Report::where('status', 'investigating')
            ->where('target_resolved', false)
            ->whereNotNull('negotiation_deadline')
            ->where('negotiation_deadline', '<=', now())
            ->get();
        $this->info("Đang xử lý " . $expiredReports->count() . "báo cáo quá hạn...");
        foreach ($expiredReports as $report) {
            //nạp thông tin phòng/hoá đơn và chủ trọ
            if ($report->reportable_type === \App\Models\Room::class) {
                $report->load(['reportable.boardingHouse.user', 'reporter']);
                $landlord = $report->reportable->boardingHouse->user ?? null;
            } elseif ($report->reportable_type === \App\Models\Invoice::class) {
                $report->load(['reportable.contract.room.boardingHouse.user', 'reporter']);
                $landlord = $report->reportable->contract->room->boardingHouse->user ?? null;
            } else {
                $report->load(['reporter']);
                $landlord = null;
            }
            //gửi thông báo quá hạn cho chủ trọ
            if ($landlord) {
                $landlord->notify(new AdminNotification(
                    'Báo cáo khiếu nại đã quá hạn thương lượng',
                    'Báo cáo #' . $report->id . ' đã quá thời hạn tự thương lượng. Hồ sơ đã chuyển lên Ban Quản Trị can thiệp.',
                    'report_deadline_expired_landlord',
                    '/landlord/reports'
                ));
                if (!empty($landlord->fcm_token)) {
                    \App\Services\FcmService::sendPushNotification(
                        $landlord->fcm_token,
                        'Báo cáo khiếu nại đã quá hạn thương lượng',
                        'Báo cáo #' . $report->id . ' đã quá thời hạn tự thương lượng. Hồ sơ đã chuyển lên Ban Quản Trị can thiệp.',
                        '/landlord/reports'
                    );
                }
            }
            //gửi thông báo cho khách thuê
            if ($report->reporter) {
                $report->reporter->notify(new AdminNotification(
                    'Báo cáo đã tự động chuyển lên Ban Quản Trị',
                    'Báo cáo #' . $report->id . ' đã hết thời hạn thương lượng trực tiếp và được chuyển cho Admin xử lý.',
                    'report_deadline_expired_reporter',
                    '/reports'
                ));
                if (!empty($report->reporter->fcm_token)) {
                    \App\Services\FcmService::sendPushNotification(
                        $report->reporter->fcm_token,
                        'Báo cáo đã tự động chuyển lên Ban Quản Trị',
                        'Báo cáo #' . $report->id . ' đã hết thời hạn thương lượng trực tiếp và được chuyển cho Admin xử lý.',
                        '/reports'
                    );
                }
            }
            //gửi thông báo cho admin
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new AdminNotification(
                    'Khiếu nại quá hạn thương lượng - Cần can thiệp',
                    'Báo cáo # ' . $report->id . ' đã hết hạn thương lượng giữa chủ trọ và khách thuê, đang chờ Admin giải quyết.',
                    'report_deadline_expired_admin',
                    '/admin/reports'
                ));
            }
            // cập nhật trạng thái báo cáo về pending để Admin xử lý
            $report->update([
                'status' => 'pending'
            ]);
        }
        $this->info("Hoàn tất gửi thông báo quá hạn.");
    }
}

?>