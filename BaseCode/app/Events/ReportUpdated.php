<?php
namespace App\Events;
use App\Models\Report;
use Carbon\Traits\Serialization;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;
    public $report;
    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('reports')
        ];
    }
}

?>