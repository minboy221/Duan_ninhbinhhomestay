<?php

namespace App\Services;

use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Exception;

class ReportService
{
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
    ];

    public function createReport(array $data, int $userId)
    {
        //phần upload ảnh bằng chứng
        $imagePaths = [];
        if (isset($data['evidence_images'])) {
            foreach ($data['evidence_images'] as $image) {
                $imagePaths[] = $image->store('reports/evidence', 'public');
            }
        }
        $settingDays = \App\Models\Setting::where('key', 'report_negotiation_days')
            ->value('value');
        $days = $settingDays ? (int) $settingDays : 2;
        $reportData = [
            'reporter_id' => $userId,
            'reportable_type' => $this->modelMap[$data['reportable_type']],
            'reportable_id' => $data['reportable_id'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'evidence_images' => $imagePaths,
            'status' => 'pending',
            'negotiation_deadline' => now()->addDays($days),
        ];
        return $this->reportRepo->create($reportData);
    }
    public function resolveSelfNegotiation(int $reportId, array $data, int $userId)
    {
        $report = $this->reportRepo->findById($reportId);
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
        }
        //bên báo cáo hài lòng -> đóng báo cáo
        if ($data['action'] === 'reporter_accept') {
            $updateData = [
                'reporter_resolved' => true,
                'status' => 'resolved',
                'resolved_at' => now(),
            ];
        }
        //không thoả thuận -> Admin xử lý
        if ($data['action'] === 'escalate_admin') {
            $updateData = [
                'status' => 'pending',
            ];
        }
        return $this->reportRepo->update($reportId, $updateData);
    }
}

?>