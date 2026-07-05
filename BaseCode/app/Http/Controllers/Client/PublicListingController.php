<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
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
        $date = $request->query('date');
        //tìm tất cả lịch hẹn của ngày của phòng này trong ngày được chọn
        $post = RoomPost::findOrFail($id);
        $roomId = $post->room_id;
        //Chỉ chặn nếu lịch hẹn đó đang ở trạng thái 'pending hoặc 'approved'/'confirmed'
        $bookedTimes = Appointment::where('room_id', $roomId)
            ->where('date', $date)
            ->whereIn('status', ['pending', 'approved', 'confirmed'])
            ->pluck('time')
            ->toArray();
        //Định dạng thời gian
        $formattedBookedTimes = array_map(function ($time) {
            return substr($time, 0, 5);
        }, $bookedTimes);
        return response()->json($formattedBookedTimes);
    }
}