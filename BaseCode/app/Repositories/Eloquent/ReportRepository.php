<?php
namespace App\Repositories\Eloquent;

use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;

class ReportRepository implements ReportRepositoryInterface
{
    public function create(array $data)
    {
        return Report::create($data);
    }

    public function findById(int $id)
    {
        return Report::with(['reportable', 'reporter', 'resolver'])->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $report = $this->findById($id);
        $report->update($data);
        return $report;
    }

    public function getUserReports(int $userId)
    {
        return Report::where('reporter_id', $userId)
            ->with('reportable')
            ->latest()
            ->paginate(10);
    }
}
?>