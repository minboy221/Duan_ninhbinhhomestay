<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\LandlordAvailability;
use App\Models\RoomPost;
use App\Models\Appointment;
use App\Services\CategoryService;
use App\Services\PublicListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class PublicListingController extends Controller
{
    protected $listingService;

    // Nạp Service mở rộng thông qua hàm khởi tạo Constructor
    public function __construct(PublicListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    //Hiển thị danh sách tin đăng của phòng trọ công khai
    public function index(Request $request, CategoryService $categoryService)
    {
        //Giao việc lọc dữ liệu cho Service
        $listings = $this->listingService->getFilteredListings($request);
        $categoryData = $categoryService->getActiveData();

        return Inertia::render('Client/timtro', [
            'listings' => $listings,
            'filters' => $request->only(['search']),
            'categories' => $categoryData['types'],
            'areas' => $categoryData['areas'],
            'amenities' => $categoryData['amenities'],
        ]);
    }

    // Hiển thị chi tiết 1 tin đăng phòng trọ
    public function show($id = null)
    {
        if ($id === null) {
            $firstPost = RoomPost::where('status', 'approved')->first();
            if (!$firstPost) {
                return redirect()->route('timtro')->with('error', 'Không tìm thấy bài đăng trọ nào.');
            }
            return redirect()->route('chitiettro', $firstPost->id);
        }

        $post = RoomPost::with(['room.boardingHouse.user', 'room.services', 'landlord'])
            ->where('status', 'approved')
            ->findOrFail($id);

        $post->timestamps = false;
        $post->increment('view_count');

        $room = $post->room;

        // Gộp dữ liệu post + room thành 1 object phẳng để Vue dùng
        $roomData = [
            'id' => $post->id, // dùng post_id để đặt lịch/booking
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'price' => $room->price,
            'area' => $room->area,
            'capacity' => $room->capacity,
            'status' => $room->status,
            'images' => $room->images ?? [],          // ảnh từ Room
            'post_images' => $post->image ?? [],           // ảnh từ RoomPost
            'amenities' => $room->amenities ?? null,
            'address' => $room->address ?? null,
            'description' => $post->description ?? null,
            'title' => $post->title ?? null,
            'boardingHouse' => $room->boardingHouse,
            'services' => $room->services ?? [],
        ];

        // Danh sách phòng tương tự (cùng nhà trọ)
        $similarPosts = RoomPost::with(['room.boardingHouse'])
            ->where('id', '!=', $post->id)
            ->where('status', 'approved')
            ->whereHas('room', function ($q) use ($room) {
                $q->where('boarding_house_id', $room->boarding_house_id);
            })
            ->limit(3)
            ->get()
            ->map(function ($p) {
                $r = $p->room;
                return [
                    'id' => $p->id,
                    'room_number' => $r->room_number ?? '-',
                    'price' => $r->price ?? 0,
                    'area' => $r->area ?? 0,
                    'images' => $r->images ?? ($p->image ?? []),
                    'address' => $r->address ?? null,
                    'boardingHouse' => $r->boardingHouse,
                ];
            });

        return Inertia::render('Client/chitiettro', [
            'room' => $roomData,
            'similarRooms' => $similarPosts,
        ]);
    }

    //Đặt Lịch hẹn xem phòng (Đã sửa sạch lỗi cú pháp ]; )
    public function book(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn phải đăng nhập để đặt lịch xem phòng.');
        }

        $todayStr = Carbon::today()->format('Y-m-d');
        $maxDate = Carbon::today()->addDays(6)->format('Y-m-d');

        $request->validate([
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($todayStr, $maxDate) {
                    if ($value < $todayStr || $value > $maxDate) {
                        $fail('Bạn chỉ có thể đặt lịch trong vòng 7 ngày tới.');
                    }
                }
            ],
            'time' => [
                'required',
                function ($attribute, $value, $fail) use ($request, $todayStr) {
                    if ($request->input('date') === $todayStr) {
                        $currentTime = Carbon::now()->format('H:i');
                        if ($value < $currentTime) {
                            $fail('Không thể chọn giờ hẹn trong quá khứ.');
                        }
                    }
                }
            ],
            'note' => 'nullable|string|max:500',
        ], [
            'date.required' => 'Vui lòng chọn ngày hẹn.',
            'time.required' => 'Vui lòng chọn giờ hẹn xem phòng.',
        ]);

        // Giao việc tạo bản ghi và thông báo cho Service giải quyết
        $this->listingService->createAppointment($id, $request->all());

        return redirect()->back()->with('success', 'Gửi yêu cầu đặt lịch hẹn xem phòng thành công! Vui lòng chờ chủ trọ phê duyệt.');
    }

    //Phần lấy danh sách các khung giờ đã bị user khác đặt trước dựa theo ngày
    public function getBookedSlots(Request $request, $id)
    {
        $dateStr = $request->query('date');
        if (!$dateStr) {
            return response()->json(['available_slots' => [], 'booked_slots' => []]);
        }
        $date = Carbon::parse($dateStr);
        $dayOfWeek = $date->dayOfWeek;

        //tìm thông tin để lấy ra ID của cơ sở trọ
        $post = RoomPost::with('room.boardingHouse')->findOrFail($id);
        $boardingHouseId = $post->room->boarding_house_id;
        $roomId = $post->room_id;

        //lấy thời gian làm việc của từ chủ trọ đặt cho toàn bộ cơ sở này
        $availabilities = LandlordAvailability::where('boarding_house_id', $boardingHouseId)
            ->where('day_of_week', $dayOfWeek)
            ->get();
        //tự set chia nhỏ thời gian thành các danh sách các slot cách nhau 30 phút
        $generatedSlots = [];
        foreach ($availabilities as $avail) {
            $start = Carbon::parse($avail->start_time);
            $end = Carbon::parse($avail->end_time);

            while ($start->lessThan($end)) {
                $generatedSlots[] = $start->format('H:i');
                $start->addMinutes(30);
            }
        }
        //lấy các giờ đã bị trùng của riêng căn phòng đó dựa trên roomId vừa tìm được
        $bookedTimes = Appointment::where('room_id', $roomId)->where('date', $dateStr)
            ->whereIn('status', ['pending', 'approved', 'confirmed'])
            ->pluck('time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();
        //trả kết quả về cho frontend
        return response()->json([
            'available_slots' => $generatedSlots,
            'booked_slots' => $bookedTimes
        ]);
    }

    // Phần lấy lịch hẹn trong ngày để thông báo đếm ngược và tự hủy
    // Phần lấy lịch hẹn trong ngày để thông báo đếm ngược và tự hủy
    public function getTodayAppointment()
    {
        $userId = auth()->id();
        $todayStr = Carbon::today()->toDateString();
        $nowTime = Carbon::now('Asia/Ho_Chi_Minh');

        // 1. Quét và tự động chuyển các lịch hẹn quá giờ hôm nay sang 'expired'
        $appointments = Appointment::with(['room.boardingHouse'])
            ->where('date', $todayStr) // Lấy các lịch hẹn ngày hôm nay
            ->where('status', 'approved')
            ->whereNull('feedback_result')
            ->get();

        foreach ($appointments as $apt) {
            // Lấy thời gian tự hủy từ cơ sở trọ (mặc định 30 phút nếu không có cấu hình)
            $cancelMinutes = $apt->room->boardingHouse->cancel_after_minutes ?? 30;

            // Tạo Carbon object cho thời điểm hẹn (ngày + giờ hẹn)
            $appointmentTime = Carbon::parse($apt->date . ' ' . $apt->time, 'Asia/Ho_Chi_Minh');

            // Nếu thời gian hiện tại đã vượt quá thời gian hẹn + số phút tự hủy
            if ($nowTime->greaterThan($appointmentTime->addMinutes($cancelMinutes))) {
                $apt->update(['status' => 'expired']);
            }
        }

        // 2. Lấy lịch hẹn sớm nhất hôm nay chưa quá giờ tự hủy để trả về cho Client hiển thị popup
        $activeAppointment = Appointment::with('room.boardingHouse')
            ->where('user_id', $userId)
            ->where('date', $todayStr)
            ->where('status', 'approved')
            ->whereNull('feedback_result')
            ->orderBy('time', 'asc')
            ->first();

        if (!$activeAppointment) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $activeAppointment->id,
            'time' => Carbon::parse($activeAppointment->time)->format('H:i'),
            'room_name' => $activeAppointment->room->room_number ?? 'Phòng Trọ',
            'address' => $activeAppointment->room->boardingHouse->address_detail ?? '',
        ]);
    }



    //phần gửi dữ liệu form feedback cho người dùng
    public function submitFeedback(Request $request, $id)
    {
        //validate dữ liệu gửi lên từ form khảo sát
        $request->validate([
            'result' => 'required|in:like,dislike',
            'reason' => 'nullable|string'
        ]);
        //tìm lịch hẹn tương ứng và kiểm tra xem có phải của user đăng nhập hay không
        $appointment = Appointment::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        //Phần khách hàng ưng ý
        if ($request->result === 'like') {
            $appointment->update([
                'status' => 'success_matched',
                'feedback_result' => 'like',
                'feedback_time' => now()
            ]);
            //gửi thông báo Notification/email cho chủ trọ
            return response()->json([
                'message' => 'Cảm ơn bạn đã phản hồi! HomeStay đã báo cho chủ nhà chuẩn bị hợp đồng',
                'recommendations' => [] //thành công thì sẽ không gợi ý phòng khác
            ]);
        }

        //Phần khách hàng không ưng ý/chưa thuê
        $appointment->update([
            'status' => 'false_matched',
            'feedback_result' => 'dislike',
            'feedback_reason' => $request->reason,
            'feedback_time' => now()
        ]);

        //phần DEMO ĐỂ GỢI Ý PHÒNG TỚI NGƯỜI DÙNG

        //tìm thông tin bài đăng của căn phòng vừa xem để lấy mốc so sánh (giá cả, khu vực)
        $currentRoomPost = RoomPost::where('room_id', $appointment->room_id)->first();
        if (!$currentRoomPost) {
            return response()->json([
                'status' => 'success',
                'message' => 'đã ghi nhận phản hồi của bạn',
                'recommendations' => []
            ]);
        }
        $district = $currentRoomPost->room->boardingHouse->district ?? null;
        $price = $currentRoomPost->room->price ?? 0;
        $query = RoomPost::where('id', '!=', $currentRoomPost->id)
            ->where('status', 'approved')
            ->whereHas('room', function ($q) use ($district, $price, $request) {
                if ($district) {
                    $q->whereHas('boardingHouse', function ($bh) use ($district) {
                        $bh->where('district', $district);
                    });
                }
                switch ($request->reason) {
                    case 'Giá cao quá':
                        $q->where('price', '<', $price);
                        break;
                    case 'Xa nơi làm việc/học tập':
                    case 'Phòng thực tế không giống với ảnh':
                        $q->whereBetween('price', [$price * 0.8, $price * 1.2]);
                        break;
                }
            });
        if ($request->reason === 'Giá cao quá') {
            $query->whereHas('room', function ($q) {
                $q->orderBy('price', 'asc');
            });
        } else {
            $query->orderBy('create_at', 'desc');
        }
        $recommendations = $query->take(3)->get();
        //trả về JSON chứa danh sách phòng gợi ý thay thế
        return response()->json([
            'messeage' => 'đã ghi nhận phản hồi của bạn',
            'recommendations' => $recommendations
        ]);
    }
}
