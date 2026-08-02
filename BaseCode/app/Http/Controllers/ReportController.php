<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\ResolveReportRequest;
use App\Services\ReportService;
use App\Repositories\Contracts\ReportRepositoryInterface;
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
            $boardingHouse = null;
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
                ->where('status', 'viewed')
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
        $this->reportService->createReport($request->validated(),$userId);
        return redirect()->back()->with('success','Gửi báo cáo thành công! Hệ thống sẽ hỗ trợ bạn xử lý');
    }
    //phần 2 bên xử lý
    public function resolveSelf(ResolveReportRequest $request, $id)
    {
        $this->reportService->resolveSelfNegotiation($id, $request->validated(), auth()->id());
        return redirect()->back()->with('success', 'Cập nhật tiến trình báo cáo thành công');
    }

    //Phần hiển thị danh sách báo cáo dành cho chủ trọ
    public function landlordIndex(){
        $landlordId = auth()->id();
        //lấy các báo cáo mà user báo cáo của chủ trọ này
        $reports = \App\Models\Report::whereHasMorph('reportable',
        [\App\Models\Room::class,
        \App\Models\Invoice::class,
        \App\Models\Contract::class
        ],
        function ($query,$type) use($landlordId){
            if($type === \App\Models\Room::class){
                $query->whereHas('boardingHouse', function ($q) use ($landlordId){
                    $q->where('user_id',$landlordId);
                });
            }elseif($type === \App\Models\Invoice::class){
                $query->whereHas('contract.room.boardingHouse', function ($q) use ($landlordId){
                    $q->where('user_id',$landlordId);
                });
            }elseif($type === \App\Models\Contract::class){
                $query->whereHas('room.boardingHouse', function ($q) use($landlordId){
                    $q->where('user_id',$landlordId);
                });
            }
        }
        )->with(['reportable','reporter'])
        ->orderBy('created_at','desc')
        ->paginate(10);
        return Inertia::render('Landlord/Reports/Index',[
            'reports' => $reports
        ]);
    }
}
