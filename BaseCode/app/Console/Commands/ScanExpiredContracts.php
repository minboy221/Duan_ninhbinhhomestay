<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Landlord\ContractController;

class ScanExpiredContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét tự động trạng thái các hợp đồng (expiring/expired) theo thời hạn thực tế';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = ContractController::scanContractStatuses();
        $this->info("Đã quét và cập nhật thành công {$count} hợp đồng.");
        return Command::SUCCESS;
    }
}
