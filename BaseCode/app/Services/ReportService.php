<?php

namespace App\Services;

use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Traits\CompressesImages;
use Illuminate\Support\Facades\Storage;
use Exception;

class ReportService
{
    //nén ảnh và giảm kích thước ảnh khi lưu trên cloudflare
    use CompressesImages;
    protected $reportRepo;

    public function __construct(ReportRepositoryInterface $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    //Mapping Alias ngắn sang Namespace đầy đủ của Model
    private array $modelMap = [
        'Post' => \App\Models\Post::class,
        'Property' => \App\Models\Property::class,
        'Contract' => \App\Models\Contract::class,
        'Invoice' => \App\Models\Invoice::class,
        'Room' => \App\Models\Room::class,
        'BoardingHouse' => \App\Models\BoardingHouse::class,
    ];

    public function createReport(array $data, int $userId)
    {
        //phần upload ảnh bằng chứng
        $imagePaths = [];
        if (isset($data['evidence_images'])) {
            $imagePaths = $this->compressAndStoreMultipleImages($data['evidence_images'], 'reports/evidence', 'r2_private');
        }

        $settingDays = \App\Models\Setting::where('key', 'report_negotiation_days')
            ->value('value');
        $days = $settingDays ? (int) $settingDays : 2;
        //nếu chọn tự giải quyết trạng thái sẽ là 'investigating (đang thương lượng) ngược lại là pending'
        $status = ($data['resolve_type'] === 'direct') ? 'investigating' : 'pending';
        $reportData = [
            'reportable_type' => $this->modelMap[$data['reportable_type']],
            'reporter_id' => $userId,
            'resolve_type' => $data['resolve_type'] ?? 'system',
            //ghi nhận hình thức báo cáo
            'reportable_id' => $data['reportable_id'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? '',
            'evidence_images' => $imagePaths,
            'status' => $status,
            'negotiation_deadline' => now()->addDays($days),
        ];

        // Tạo báo cáo
        $report = $this->reportRepo->create($reportData);

        // Tải liên kết thông tin phòng trọ và gửi thông báo phù hợp
        $room = null;
        $invoice = null;

        if ($report->reportable_type === \App\Models\Room::class) {
            $room = \App\Models\Room::with('boardingHouse.user')->find($report->reportable_id);
            $landlord = $room->boardingHouse->user ?? null;
            // Gửi thông báo tới chủ trọ
            if ($landlord) {
                $title = ($report->resolve_type === 'direct') ? 'Yêu cầu tự giải quyết khiếu nại: ' : 'Có khiếu nại mới từ khách thuê';
                $content = 'Phòng ' . $room->room_number . ' tại cơ sở của bạn đang bị khiếu nại với lý do: ' . $report->reason;
                if ($report->resolve_type === 'direct') {
                    $content .= '. Vui lòng chủ động liên hệ với khách thuê để xử lý trực tiếp.';
                }
                $landlord->notify(new \App\Notifications\AdminNotification($title, $content, 'new_report_landlord', '/landlord/reports'));
            }
        } elseif ($report->reportable_type === \App\Models\Invoice::class) {
            // Lấy hóa đơn liên kết với hợp đồng và phòng
            $invoice = \App\Models\Invoice::with('contract.room.boardingHouse.user')->find($report->reportable_id);
            $room = $invoice->contract->room ?? null;
            $landlord = $room->boardingHouse->user ?? null;
            // Gửi thông báo khiếu nại hóa đơn tới chủ trọ
            if ($landlord) {
                $title = ($report->resolve_type === 'direct') ? 'Yêu cầu tự giải quyết hoá đơn ' : 'Khiếu nại hoá đơn mới';
                $content = 'Hoá đơn mã #' . $invoice->invoice_code . 'của phòng' . ($room->room_number ?? 'N/A') . ' bị khiếu nại với lý do: ' . $report->reason;
                if ($report->resolve_type === 'direct') {
                    $content . '. Vui lòng chủ động liên hệ với khách hàng thuê để giải quyết trực tiếp.';
                }
                $landlord->notify(new \App\Notifications\AdminNotification($title, $content, 'new_report_landlord', '/landlord/reports'));
            }
        }

        // Gửi thông báo tới hệ thống Admin (chỉ gửi khi user chọn báo cáo lên hệ thống)
        if ($report->resolve_type === 'system') {
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                if ($invoice) {
                    $msg = 'Khách thuê' . auth()->user()->name . ' vừa báo cáo sai lệch hoá đơn #' . $invoice->invoice_code . '(phòng ' . ($room->room_number ?? 'N/A') . ') lên Admin.';
                } else {
                    $msg = 'Khách thuê' . auth()->user()->name . ' vừa báo cáo vi phạm phòng ' . ($room->room_number ?? 'N/A') . 'lên Admin.';
                }
                $admin->notify(new \App\Notifications\AdminNotification(
                    'có báo cáo vi phạm mới',
                    $msg,
                    'new_report_admin',
                    '\admin\reports'
                ));
            }
        }
        return $report;
    }
    public function resolveSelfNegotiation(int $reportId, array $data, int $userId)
    {
        $report = $this->reportRepo->findById($reportId);

        // Nạp quan hệ động dựa trên loại báo cáo để tránh gọi boardingHouse trên Invoice
        if ($report->reportable_type === \App\Models\Room::class) {
            $report->load(['reportable.boardingHouse.user', 'reporter']);
        } elseif ($report->reportable_type === \App\Models\Invoice::class) {
            $report->load(['reportable.contract.room.boardingHouse.user', 'reporter']);
        } else {
            $report->load(['reporter']);
        }
        //Upload bằng chứng phảm hồi & khắc phục
        $responseImages = [];
        if (isset($data['response_evidence'])) {
            foreach ($data['response_evidence'] as $image) {
                $responseImages[] = $image->store('reports/responses', 'public');
            }
        }
        $updateData = [];
        //bên gửi tố cáo / xác nhận đã khắc phục
        if ($data['action'] === 'target_resolve') {
            $updateData = [
                'target_resolved' => true,
                'response_note' => $data['response_note'],
                'response_evidence' => $responseImages,
                'status' => 'investigating',
            ];
            $reporter = $report->reporter;
            if ($reporter) {
                $roomName = '';
                if ($report->reportable_type === \App\Models\Room::class) {
                    $roomName = 'phòng ' . ($report->reportable->room_number ?? '');
                } elseif ($report->reportable_type === \App\Models\Invoice::class) {
                    $roomName = 'hóa đơn #' . ($report->reportable->invoice_code ?? '');
                } else {
                    $roomName = 'báo cáo #' . $report->id;
                }

                $reporter->notify(new \App\Notifications\AdminNotification(
                    'Chủ trọ phản hồi giải trình báo cáo',
                    'Chủ trọ vừa gửi nội dung giải trình/khắc phục cho ' . $roomName . ' của bạn.',
                    'report_landlord_responded',
                    '/reports'
                ));
            }
        }
        //bên báo cáo hài lòng -> đóng báo cáo
        if ($data['action'] === 'reporter_accept') {
            $updateData = [
                'reporter_resolved' => true,
                'status' => 'resolved',
                'resolved_at' => now(),
            ];
            $landlord = null;
            if ($report->reportable_type === \App\Models\Room::class) {
                $landlord = $report->reportable->boardingHouse->user ?? null;
            } elseif ($report->reportable_type === \App\Models\Invoice::class) {
                $landlord = $report->reportable->contract->room->boardingHouse->user ?? null;
            }
            if ($landlord) {
                $landlord->notify(new \App\Notifications\AdminNotification(
                    'Khách thuê đã đóng khiếu nại',
                    'Khách thuê đã chấp nhận phản hồi giải trình và đóng khiếu nại #' . $report->id . '.',
                    'report_resolved_success',
                    '/landlord/reports'
                ));
            }
        }
        //không thoả thuận -> Admin xử lý
        if ($data['action'] === 'escalate_admin') {
            $updateData = [
                'status' => 'pending',
            ];
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\AdminNotification(
                    'Khiếu nại chuyển cấp lên Ban quản trị',
                    'Khiếu nại #' . $report->id . ' không đạt được thỏa thuận tự xử lý và đã chuyển cấp cho Admin.',
                    'report_escalated_admin',
                    '/admin/reports'
                ));
            }
            // Đồng thời thông báo cho Chủ trọ biết trạng thái chuyển cấp
            $landlord = null;
            if ($report->reportable_type === \App\Models\Room::class) {
                $landlord = $report->reportable->boardingHouse->user ?? null;
            } elseif ($report->reportable_type === \App\Models\Invoice::class) {
                $landlord = $report->reportable->contract->room->boardingHouse->user ?? null;
            }
            if ($landlord) {
                $landlord->notify(new \App\Notifications\AdminNotification(
                    'Khiếu nại đã chuyển lên Admin can thiệp',
                    'Báo cáo vi phạm #' . $report->id . ' đã chuyển lên Ban Quản Trị để xác minh và giải quyết.',
                    'report_escalated_landlord',
                    '/landlord/reports'
                ));
            }
        }
        $updatedReport = $this->reportRepo->update($reportId, $updateData);
        event(new \App\Events\ReportUpdated($updatedReport));
        return $updatedReport;
    }
}
?>