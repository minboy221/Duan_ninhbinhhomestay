<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\ResolveReportRequest;
use App\Services\ReportService;
use App\Repositories\Interfaces\ReportRepositoryInterface;
use Inertia\Inertia;

class ReportController extends Controller
{
    protected $reportService;
    protected $reportRepo;

    public function __construct(ReportService $reportService, ReportRepositoryInterface $reportRepo)
    {
        $this->reportService = $reportService;
        $this->reportRepo = $reportRepo;
    }

    //phần hiển thị danh sách
    public function index()
    {
        $reports = $this->reportRepo->getUserReports(auth()->id());
        return Inertia::render('Profile/listbaocao', ['reports' => $reports]);
    }

    //tạo báo cáo mới
    public function store(StoreReportRequest $request)
    {
        $userId = auth()->id();
        $type = $request->reportable_type;
        $id = $request->reportable_id;
        //ràng buộc điều kiện tố cáo với phòng hoặc cơ sở trọ
        if (in_array($type, ['Room', 'Property', 'BoardingHouse'])) {
            $roomId = null;
            $boardingHouseId = null;
            if ($type === 'Room') {
                $roomId = $id;
                $room = \App\Models\Room::find($id);
                if ($room) {
                    $boardingHouseId = $room->boarding_house_id;
                }
            } else {
                //đối với Property hoặc BoardingHouse, id là của cơ sở trọ
                $boardingHouseId = $id;
            }
            //Check xem người dùng đã từng có hợp đồng thuê chưa
            $hasContract = \App\Models\Contract::where('tenant_id', $userId)
                ->whereHas('room', function ($q) use ($roomId, $boardingHouseId) {
                    if ($roomId) {
                        $q->where('id', $roomId);
                    }
                    if ($boardingHouseId) {
                        $q->where('boarding_house_id', $boardingHouseId);
                    }
                })->exists();
            //check user đã có lịch hẹn xem phòng ở trạng thái 'viewed' chưa
            $hasAppointment = \App\Models\Appointment::where('user_id', $userId)
                ->whereIn('status', ['viewed', 'success_matched', 'false_matched'])
                ->wherehas('room', function ($q) use ($roomId, $boardingHouseId) {
                    if ($roomId) {
                        $q->where('id', $roomId);
                    }
                    if ($boardingHouseId) {
                        $q->where('boarding_house_id', $boardingHouseId);
                    }
                })->exists();
            //nếu cả 2 điều kiện đều không thoả mãn thì chặn tố cáo
            if (!$hasContract && !$hasAppointment) {
                return redirect()->back()->with('error', 'Bạn chỉ được quyền báo cáo phòng hoặc cơ sở này nếu đã từng thê hoặc đã đặt lịch hẹn và tới xem phòng thực tế.');
            }
        }
        //nếu thoả mãn điều kiện hoặc báo cáo thì tiến hành lưu báo cáo
        $this->reportService->createReport($request->validated(), $userId);
        return redirect()->back()->with('success', 'Gửi báo cáo thành công! Hệ thống sẽ hỗ trợ bạn xử lý');
    }
    //phần 2 bên xử lý
    public function resolveSelf(ResolveReportRequest $request, $id)
    {
        $this->reportService->resolveSelfNegotiation($id, $request->validated(), auth()->id());
        return redirect()->back()->with('success', 'Cập nhật tiến trình báo cáo thành công');
    }

    // Phần hiển thị danh sách báo cáo dành cho chủ trọ
    public function landlordIndex()
    {
        $landlordId = auth()->id();
        $selectedHouseId = session('selected_boarding_house_id');

        // 1. Lấy tất cả ID cơ sở trọ thuộc sở hữu chủ trọ này
        $houseIds = \App\Models\BoardingHouse::where('user_id', $landlordId)->pluck('id')->toArray();
        $propertyIds = \App\Models\Property::where('landlord_id', $landlordId)->pluck('id')->toArray();
        $allHouseIds = array_unique(array_merge($houseIds, $propertyIds));

        // Nếu chủ trọ chọn 1 Cơ sở cụ thể ở Header -> Lọc theo Cơ sở đó. Nếu chọn Tất cả -> Lấy toàn bộ.
        if ($selectedHouseId && in_array($selectedHouseId, $allHouseIds)) {
            $activeHouseIds = [(int) $selectedHouseId];
        } else {
            $activeHouseIds = $allHouseIds;
        }

        // 2. Lấy tất cả ID phòng trọ thuộc về các cơ sở này
        $roomQuery = \App\Models\Room::whereIn('boarding_house_id', $activeHouseIds);
        if (\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'property_id')) {
            $roomQuery->orWhereIn('property_id', $activeHouseIds);
        }
        $roomIds = $roomQuery->pluck('id')->toArray();

        // 3. Lấy tất cả ID hợp đồng của các phòng này
        $contractIds = \App\Models\Contract::whereIn('room_id', $roomIds)->pluck('id')->toArray();

        // 4. Lấy tất cả ID hóa đơn của các hợp đồng này
        $invoiceIds = \App\Models\Invoice::whereIn('contract_id', $contractIds)->pluck('id')->toArray();

        // 5. Truy vấn danh sách báo cáo thuộc sở hữu chủ trọ (lọc thông minh theo cơ sở đang chọn hoặc tất cả)
        $reports = \App\Models\Report::where(function ($q) use ($roomIds, $contractIds, $invoiceIds, $activeHouseIds) {
            // Khiếu nại về Phòng
            $q->orWhere(function ($sub) use ($roomIds) {
                $sub->whereIn('reportable_type', [\App\Models\Room::class, 'Room', 'App\Models\Room'])
                    ->whereIn('reportable_id', $roomIds);
            });

            // Khiếu nại về Hóa đơn
            $q->orWhere(function ($sub) use ($invoiceIds) {
                $sub->whereIn('reportable_type', [\App\Models\Invoice::class, 'Invoice', 'App\Models\Invoice'])
                    ->whereIn('reportable_id', $invoiceIds);
            });

            // Khiếu nại về Hợp đồng
            $q->orWhere(function ($sub) use ($contractIds) {
                $sub->whereIn('reportable_type', [\App\Models\Contract::class, 'Contract', 'App\Models\Contract'])
                    ->whereIn('reportable_id', $contractIds);
            });

            // Khiếu nại về Cơ sở trọ
            $q->orWhere(function ($sub) use ($activeHouseIds) {
                $sub->whereIn('reportable_type', [\App\Models\BoardingHouse::class, \App\Models\Property::class, 'BoardingHouse', 'Property', 'App\Models\BoardingHouse', 'App\Models\Property'])
                    ->whereIn('reportable_id', $activeHouseIds);
            });
        })
            ->with(['reporter'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Fallback nạp chi tiết thông tin Phòng / Tầng / Đối tượng bị khiếu nại để hiển thị ra bảng
        $reports->getCollection()->transform(function ($report) {
            $typeStr = $report->reportable_type ?? '';
            $roomNumber = null;
            $floorName = null;

            if (str_contains($typeStr, 'Room')) {
                $room = \App\Models\Room::with('floor')->find($report->reportable_id);
                if ($room) {
                    $report->setRelation('reportable', $room);
                    $roomNumber = $room->room_number ?? null;
                    $floorName = $room->floor->name ?? null;
                }
            } elseif (str_contains($typeStr, 'Invoice')) {
                $invoice = \App\Models\Invoice::with('contract.room.floor')->find($report->reportable_id);
                if ($invoice) {
                    $report->setRelation('reportable', $invoice);
                    $roomNumber = $invoice->contract->room->room_number ?? null;
                    $floorName = $invoice->contract->room->floor->name ?? null;
                }
            } elseif (str_contains($typeStr, 'Contract')) {
                $contract = \App\Models\Contract::with('room.floor')->find($report->reportable_id);
                if ($contract) {
                    $report->setRelation('reportable', $contract);
                    $roomNumber = $contract->room->room_number ?? null;
                    $floorName = $contract->room->floor->name ?? null;
                }
            } elseif (str_contains($typeStr, 'BoardingHouse') || str_contains($typeStr, 'Property')) {
                $house = \App\Models\BoardingHouse::find($report->reportable_id) ?: \App\Models\Property::find($report->reportable_id);
                if ($house) {
                    $report->setRelation('reportable', $house);
                }
            }

            $report->room_number = $roomNumber;
            $report->floor_name = $floorName;
            return $report;
        });

        return Inertia::render('Landlord/Reports/Index', [
            'reports' => $reports
        ]);
    }

}
