<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\User;
use App\Services\ContractService;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\ExtendContractRequest;
use App\Http\Requests\LiquidateContractRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Contracts\Service\Attribute\Required;

class ContractController extends Controller
{
    protected ContractService $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }
    //check tài khoản user theo Email & SĐT
    public function searchTenant(Required $required)
    {
        $query = trim($required->get('q', ''));
        if (empty($query)) {
            return response()->json([]);
        }
        $users = User::where(function ($q) use ($query) {
            $q->where('phone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%");
        })
            ->select('id', 'name', 'phone', 'email', 'cccd_number')
            ->limit(10)
            ->get();
        return response()->json($users);
    }
    //Phần tạo hợp đồng mới
    public function storeDraftAndExport(StoreContractRequest $request)
    {
        try {
            $file = $request->file('contract_file');
            $this->contractService->createContract($request->validated(), $file);
            return redirect()->back()->with('success', 'Hợp đồng đã được ký kết và lưu trữ thành công! trạng thái phòng chuyển sang đã thuê.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    //quét tự động trạng thái hợp đồng
    public static function scanContractStatuses($landlordId = null)
    {
        $service = new ContractService();
        return $service->scanContractStatuses($landlordId);
    }
    public function scanContracts(Request $request)
    {
        $count = $this->contractService->scanContractStatuses(Autha::id());
        return redirect()->back()->with('success', "Đã quét và cập nhật trạng thái cho  {$count} hợp đồng!");
    }
    //chuyển trạng thái hợp đồng sang hết hạn(Báo chấm dứt sớm hoặc hết hạn thường)
    public function markAsExpired(Request $request, Contract $contract)
    {
        if ($contract->room->boadingHouse->user_id !== Auth::id() && $contract->tenant_id !== Auth::id()) {
            abort(403);
        }
        if (!in_array($contract->status, ['active', 'signed', 'expiring'])) {
            return redirect()->back()->with('error', 'Hợp đồng phải đang trong trạng thái hiệu lực mới có thể chuyển sang Hết hạn.');
        }
        $today = now()->format('Y-m-d');
        $endDateStr = $contract->end_date ? (is_string($contract->end_date) ? substr($contract->end_date, 0, 10) : $contract->end_date->format('Y-m-d')) : null;
        $isEarlyTermination = $endDateStr && $endDateStr > $today;
        if ($isEarlyTermination) {
            $request->validate([
                'reason' => 'required|string|max:10000',
            ], [
                'reason.required' => 'Hợp đồng chưa tới ngày hết hạn. Vui lòng nhập lý do báo chấm dứt trước thời hạn.'
            ]);
        }
        $reasonText = trim($request->input('reason', ''));
        if ($isEarlyTermination) {
            $reasonText = 'Chấm dứt trước thời hạn:' . $reasonText;
        } else {
            $reasonText = $reasonText ?: "Hợp đồng đã đến thời hạn kết thúc.";
        }
        $this->contractService->markAsExpired($contract, $reasonText);
        return redirect()->back()->with('success', 'Đã chuyển trạng thái hợp đồng sang hết hạn thành công!');
    }

    //Phần thanh lý hợp đồng
    public function liquidateContract(LiquidateContractRequest $request, Contract $contract)
    {
        if ($contract->room->boadingHouse->user_id !== Auth::id()) {
            abort(403);
        }
        if ($contract->status !== 'expired') {
            return redirect()->back()->with('error', 'Hợp đồng phải bước vào trạng thái Hết hạn (expired) mới được phép được thanh lý.');
        }
        try {
            $this->contractService->liquidateContract($contract, $request->validated());
            return redirect()->back()->with('success', 'Thanh lý hợp đồng thành công! Phòng trọ đã được giải phóng trở lại trạng thái Trống.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    //phần gia hạn hợp đồng
    public function extendContract(ExtendContractRequest $request, Contract $contract)
    {
        if ($contract->room->boadingHouse->user_id !== Auth::id()) {
            abort(403);
        }
        try {
            $this->contractService->extendContract($contract, $request->validated(), Auth::id());
            return redirect()->back()->with('success', 'Hợp đồng đã được gia hạn thành công đến ngày ' . date('d/m/Y', strtotime($request->new_end_date)));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
