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
use function Laravel\Prompts\alert;

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
        $filteredData = $this->listingService->getFilteredListings($request);
        $categoryData = $categoryService->getActiveData();

        return Inertia::render('Client/timtro', [
            'listings' => $filteredData['listings'],
            'ai_parsed' => $filteredData['ai_parsed'],
            'filters' => $request->only(['search', 'ai_prompt', 'area_id', 'price', 'dientich', 'categories', 'amenities', 'price_min', 'price_max', 'floor']),
            'categories' => $categoryData['types'],
            'areas' => $categoryData['areas'],
            'amenities' => $categoryData['amenities'],
        ]);
    }

    // API phân tích nhanh prompt AI cho Client
    public function parseAiSearch(Request $request, \App\Services\AiRoomSearchService $aiSearchService)
    {
        $prompt = (string) $request->input('prompt', '');
        $result = $aiSearchService->parseSearchPrompt($prompt);
        return response()->json($result);
    }

    // API Trợ lý AI Chatbot gợi ý phòng trọ toàn trang
    public function chatAiAssistant(Request $request)
    {
        $prompt = (string) $request->input('prompt', '');
        $userId = auth()->id();
        $result = $this->listingService->searchRoomsForChatAssistant($prompt, $userId);
        return response()->json($result);
    }

    // Lấy lịch sử chat trong 7 ngày của tài khoản
    public function getChatHistory(Request $request)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json([]);
        }
        $histories = $this->listingService->getChatHistory($userId);
        return response()->json($histories);
    }

    // Xóa sạch lịch sử chat của tài khoản
    public function clearChatHistory(Request $request)
    {
        $userId = auth()->id();
        if ($userId) {
            $this->listingService->clearChatHistory($userId);
        }
        return response()->json(['success' => true]);
    }

    // Đồng bộ lịch sử từ localStorage của khách vãng lai khi vừa đăng nhập
    public function syncGuestHistory(Request $request)
    {
        $userId = auth()->id();
        $messages = (array) $request->input('messages', []);
        if ($userId && !empty($messages)) {
            $this->listingService->syncGuestChatHistory($userId, $messages);
        }
        return response()->json(['success' => true]);
    }

    // Hiển thị chi tiết 1 tin đăng phòng trọ
    public function show($slug = null)
    {
        if ($slug === null) {
            $firstPost = RoomPost::where('status', 'approved')->first();
            if (!$firstPost) {
                return redirect()->route('timtro')->with('error', 'Không tìm thấy bài đăng trọ nào.');
            }
            return redirect()->route('chitiettro', $firstPost->slug_with_hash);
        }
        $postId = $slug;
        if (!is_numeric($slug)) {
            $parts = explode('-', $slug);
            $hash = end($parts);
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hash);
            if (empty($decoded)) {
                abort(404, 'Không tìm thấy bài đăng trọ.');
            }
            $postId = $decoded[0];
        }
        $userId = auth()->id();
        $ipAddress = request()->ip();
        $data = $this->listingService->getPublicPostDetails($postId, $userId, $ipAddress);
        $post = $data['post'];
        $room = $data['room'];
        $reviews = $data['reviews'];
        $boardingHouseRating = $data['boardingHouseRating'];
        $boardingHouseReviewCount = $data['boardingHouseReviewCount'];

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
            'boardingHouseRating' => $boardingHouseRating,
            'boardingHouseReviewCount' => $boardingHouseReviewCount,
            'services' => $room->services ?? [],
            'reviews' => $reviews,
        ];
        //Tính toán quyền báo cáo vi phạm/ nhà trọ
        $hasReportPermission = false;
        if ($userId) {
            $roomId = $room->id;
            $boardingHouseId = $room->boarding_house_id;
            //check người dùng đã từng có hợp đồng thuê phòng này chưa
            $hasContract = \App\Models\Contract::where('tenant_id', $userId)->whereHas('room', function ($q) use ($roomId, $boardingHouseId) {
                if ($roomId) {
                    $q->where('id', $roomId);
                }
                if ($boardingHouseId) {
                    $q->where('boarding_house_id', $boardingHouseId);
                }
            })->exists();
            //check user đã từng đặt lịch hẹn và xem phòng chưa
            $hasAppointment = \App\Models\Appointment::where('user_id', $userId)->whereIn('status', ['viewed', 'success_matched', 'false_matched'])->whereHas('room', function ($q) use ($roomId, $boardingHouseId) {
                if ($roomId) {
                    $q->where('id', $roomId);
                }
                if ($boardingHouseId) {
                    $q->where('boarding_house_id', $boardingHouseId);
                }
            })->exists();
            $hasReportPermission = $hasContract || $hasAppointment;
        }
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
        //lấy các danh sách lý do báo cáo được is_active
        $reasons = \App\Models\ReportReason::where('is_active', true)->pluck('reason');

        return Inertia::render('Client/chitiettro', [
            'room' => $roomData,
            'similarRooms' => $similarPosts,
            'reasons' => $reasons,
            'hasReportPermission' => $hasReportPermission
        ]);
    }
    //Đặt Lịch hẹn xem phòng (Đã sửa sạch lỗi cú pháp ]; )
    public function book(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn phải đăng nhập để đặt lịch xem phòng.');
        }

        //check tài khoản là chủ trọ
        if (Auth::user()->role === 'landlord') {
            return redirect()->back()->with('error', 'tài khoản chủ trọ không được phép đặt lịch xem phòng');
        }

        $post = RoomPost::with('room')->findOrFail($id);

        $todayStr = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $maxDate = Carbon::now('Asia/Ho_Chi_Minh')->addDays(7)->format('Y-m-d');

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
                        $currentTime = Carbon::now('Asia/Ho_Chi_Minh')->format('H:i');
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

        $roomId = $post->room_id;
        //Thêm dàng buộc cho user nếu đang có hợp đồng active tại cơ sở khác, chặn không cho đặt lịch xem phòng
        $hasActiveContract = \App\Models\Contract::where('tenant_id',Auth::id())
        ->whereIn('status',['signed','active','awaiting_upload','expiring','termination_requested'])
        ->exists();
        //check nếu khách là thành viên ở ghép đang ở trong phòng
        if(!$hasActiveContract){
            $hasActiveContract = \App\Models\RoomResident::where('user_id',Auth::id())
            ->where('status','active')
            ->exists();
        }
        //nếu đang có hợp đồng/ ở trọ -> chặn  không cho đặt lịch bất kỳ tin đăng phòng trọ khác
        if($hasActiveContract){
            return redirect()->back()->with('error','Bạn đang có hợp đồng thuê phòng còn hiệu lực trên hệ thống. Không được phép đặt lịch xem các tin đăng khác!');
        }
        //kiểm tra khung giờ này đã có người đặt trước hoặc đang chờ duyểt
        if ($this->listingService->isSlotOccupied($roomId, $request->date, $request->time)) {
            return redirect()->back()->with('error', 'Khung giờ này đã được đặt hoặc đang chờ duyệt. Vui lòng chọn khung giờ khác');
        }
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
        $todayStr = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();
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
        $appointment = Appointment::with(['user', 'room.boardingHouse'])->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        //Phần khách hàng ưng ý
        if ($request->result === 'like') {
            $appointment->update([
                'status' => 'waiting_contract',
                'feedback_result' => 'interested',
                'feedback_time' => now()
            ]);
            //gửi thông báo Notification/email cho chủ trọ
            $landlord = \App\Models\User::find($appointment->landlord_id);
            if ($landlord) {
                $landlord->notify(new \App\Notifications\TenantInterestedNotification($appointment));
            }
            return response()->json([
                'message' => 'Cảm ơn bạn đã phản hồi! HomeStay đã báo cho chủ nhà chuẩn bị hợp đồng',
                'recommendations' => []
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
        $query = RoomPost::select('room_posts.*')
            ->join('rooms', 'room_posts.room_id', '=', 'rooms.id')
            ->where('room_posts.id', '!=', $currentRoomPost->id)
            ->where('room_posts.status', 'approved')
            ->whereHas('room', function ($q) use ($district, $price, $request) {
                if ($district) {
                    $q->whereHas('boardingHouse', function ($bh) use ($district) {
                        $bh->where('district', $district);
                    });
                }
                $reasonLower = mb_strtolower(trim($request->reason));
                switch ($reasonLower) {
                    case 'giá cao quá':
                        $q->where('price', '<', $price);
                        break;
                    case 'xa nơi làm việc/học tập':
                    case 'phòng thực tế không giống với ảnh':
                        $q->whereBetween('price', [$price * 0.8, $price * 1.2]);
                        break;
                }
            });

        if ($request->reason === 'Giá cao quá') {
            $query->orderBy('rooms.price', 'asc');
        } else {
            $query->orderBy('room_posts.created_at', 'desc');
        }
        $recommendations = $query->take(3)->get();
        //trả về JSON chứa danh sách phòng gợi ý thay thế
        return response()->json([
            'messeage' => 'đã ghi nhận phản hồi của bạn',
            'recommendations' => $recommendations
        ]);
    }
    
    // Lưu bình luận trực tiếp từ trang chi tiết phòng
    public function submitDirectReview(Request $request, \App\Models\Room $room)
    {
        if (!Auth::check()) {
            return redirect()->back()->with('error', 'Bạn cần đăng nhập để bình luận.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:500'
        ]);

        $user = Auth::user();
        
        $isAdmin = $user->role === 'admin';
        $isOwner = $room->boardingHouse && $user->id === $room->boardingHouse->user_id;
        
        // Kiểm tra xem user có hợp đồng hoặc lịch hẹn không (có quyền bình luận không)
        $hasContract = \App\Models\Contract::where('tenant_id', $user->id)
            ->where('room_id', $room->id)
            ->exists();
            
        $hasAppointment = \App\Models\Appointment::where('user_id', $user->id)
            ->where('room_id', $room->id)
            ->exists();
            
        if (!$isAdmin && !$isOwner && !$hasContract && !$hasAppointment) {
            return redirect()->back()->with('error', 'Bạn phải từng thuê hoặc xem phòng này mới có thể đánh giá.');
        }

        // Tạo review
        \App\Models\Review::create([
            'tenant_id' => $user->id,
            'boarding_house_id' => $room->boarding_house_id,
            'room_id' => $room->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $message = ($isAdmin || $isOwner) ? 'Đã ghim thông báo thành công!' : 'Cảm ơn bạn đã đánh giá!';
        return redirect()->back()->with('success', $message);
    }
}
