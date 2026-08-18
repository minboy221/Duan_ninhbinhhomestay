<?php

namespace App\Http\Controllers;
use App\Models\RoomPost;
use App\Models\Amenity;
use App\Models\Area;
use App\Models\Appointment;
use App\Notifications\NewAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Controller;
use Inertia\Inertia;
use App\Models\BoardingHouse;
use App\Models\Room;
use App\Http\Requests\StoreRoomPostRequest;
use App\Http\Requests\UpdateRoomPostRequest;
use App\Services\RoomListingService;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

class RoomListingController extends Controller
{
    protected $roomPostService;

    //cho Services vào thông qua hàm khởi tạo
    public function __construct(RoomListingService $roomPostService)
    {
        $this->roomPostService = $roomPostService;
    }
    //phần hiển thị danh sách tin đăng
    public function index(): Response
    {
        //gọi services lấy danh sách bài đăng
        $listings = $this->roomPostService->getLandlordPosts(auth()->id());
        //trả dữ liệu ra giao diện
        return Inertia::render('Landlord/Listings/index', [
            'listings' => $listings
        ]);
    }

    // Phần hiển thị form nhập đăng tin
    public function create(): Response
    {
        $boardingHousesId = session('selected_boarding_house_id');
        $boardingHouses = BoardingHouse::where('user_id', auth()->id())
            ->where('id', $boardingHousesId)
            //lọc theo cơ sở đang chọn
            ->where('status', 'approved')
            ->with(['floors.rooms.roomPosts'])
            ->get();
        return
            Inertia::render('Landlord/Listings/Create', [
                'boardingHouses' => $boardingHouses
            ]);
    }
    //API lấy thông tin phòng chp frontend
    public function getRoomDetails($id): JsonResponse
    {
        $room = Room::with(['services', 'boardingHouse', 'floor'])->findOrFail($id);
        if ($room->boardingHouse?->user_id !== auth()->id()) {
            return response()->json(['message' => 'không có quyền truy cập'], 403);
        }
        return response()->json($room);
    }

    public function store(StoreRoomPostRequest $request)
    {
        $user = auth()->user();
        $room = \App\Models\Room::findOrFail($request->room_id);
        //tính thứ tự phòng của chủ trọ trong db
        $roomOrderIndex = \App\Models\Room::whereHas('boardingHouse', function ($q) use ($user){
            $q->where('user_id',$user->id);
        })->where('id', '<=', $room->id)->count();
        //nếu phòng này bị đóng băng, chặn không cho đăng tin
        if($user->isRoomFrozen($roomOrderIndex)){
            return redirect()->back()->with('error','Phòng này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ.Vui lòng nâng cấp gói để đăng tin cho thuê!');
        }
        $currentListings = RoomPost::where('landlord_id', $user->id)->count();
        if(!$user->canCreateResource('max_listings',$currentListings)){
            return redirect()->back()->with('error','Bạn đã đạt giới hạn số lượng bài đăng của gói hiện tại!');
        }
        //xác định trạng thái dựa trên btn ở frontend
        $status = $request->input('action') === 'draft' ? 'draft' : 'pending';
        //gọi đến services sử lý
        $this->roomPostService->createPost(
            $request->validated(),
            $request->file('images') ?? [],
            $status
        );
        $msg = $status === 'draft' ? 'đã lưu tin đăng vào danh sách nháp' : 'đã gửi bài đăng cho admin duyệt';

        return redirect()->route('landlord.listings.index')->with('success', $msg);
    }

    public function update(UpdateRoomPostRequest $request, $id)
    {
        $post = RoomPost::findOrFail($id);
        if ($post->landlord_id !== auth()->id()) {
            abort(403);
        }

        $status = $request->input('action') === 'draft' ? 'draft' : 'pending';

        $this->roomPostService->updatePost(
            $post,
            $request->validated(),
            $request->file('images'),
            $status
        );

        $msg = $status === 'draft' ? 'Đã cập nhật dữ liệu và lưu nháp!' : 'Đã cập nhật và gửi bài đăng lại cho Admin!';

        return redirect()->route('landlord.listings.index')->with('success', $msg);
    }
    //phần lấy tiện ích của phòng
    public function getRoomServices($id)
    {
        $room = Room::with('services')->findOrFail($id);

        return response()->json([
            'services' => $room->services,
            'room_number' => $room->room_number,
            'price' => $room->price,
        ]);
    }

    //phần hiển thị giao diện sửa tin đăng
    public function edit($id): Response
    {
        //load thông tin của phòng, tầng, nhà trọ hiện tại của bài viết
        $post = RoomPost::with(['room.floor', 'room.boardingHouse'])->findOrFail($id);
        if ($post->landlord_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền sửa bài đăng này');
        }
        $boardingHouses = BoardingHouse::where('user_id', auth()->id())
            ->where('id', $post->room->boarding_house_id)
            //khoá theo cơ sở của bài đăng
            ->where('status', 'approved')
            ->with(['floors.rooms.roomPosts'])
            ->get();
        return Inertia::render('Landlord/Listings/Edit', [
            'post' => $post,
            'boardingHouses' => $boardingHouses
        ]);
    }
    // Phần xoá tin đăng
    public function destroy($id)
    {
        $post = RoomPost::findOrFail($id);

        if ($post->landlord_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền xoá bài đăng này');
        }

        //bảo mật nếu tin đăng là công khai thì sẽ không cho xoá
        if ($post->status === 'approved') {
            return redirect()->back()
                ->with('error', 'hệ thống từ chối,bạn không thể xoá tin đăng ở trạng thái công khai');
        }
        $this->roomPostService->deletePost($post);
        return redirect()->route('landlord.listings.index')
            ->with('success', 'Đã xoá bài đăng thành công!');
    }

    public function show($id)
    {
        $data = $this->roomPostService->getPostDetailsForLandlord($id, auth()->id());

        return inertia('Landlord/Listings/Show', $data);
    }

    //Phần đóng tin đăng
    public function close($id)
    {
        $post = RoomPost::findOrFail($id);
        //check tài khoản chủ trọ
        if ($post->landlord_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền đóng bài đăng này');
        }
        //chuyển trạng thái
        $post->update(['status' => 'hidden']);
        return redirect()->route('landlord.listings.index')
            ->with('success', 'Đã đóng tin đăng thành công! tin đăng đã được gỡ bỏ');
    }


    public function getFilteredListings(Request $request)
    {
        $query = RoomPost::with(['room.boardingHouse', 'landlord'])
            ->where('status', 'approved');

        // Tìm kiếm theo tiêu đề
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Lọc theo khu vực
        if ($request->filled('area_id')) {
            $area = Area::find($request->input('area_id'));
            if ($area) {
                $query->whereHas('room.boardingHouse', function ($q) use ($area) {
                    $q->where('address_detail', 'like', "%{$area->name}%"); // Đã sửa lỗi address_detaill
                });
            }
        }

        // Lọc theo khoảng giá
        if ($request->filled('price')) {
            $priceRange = $request->input('price');
            $query->whereHas('room', function ($q) use ($priceRange) {
                if ($priceRange === 'duoi-1-trieu') {
                    $q->where('price', '<', 1000000);
                } elseif ($priceRange === '1-2-trieu') {
                    $q->whereBetween('price', [1000000, 2000000]);
                } elseif ($priceRange === '2-3-trieu') {
                    $q->whereBetween('price', [2000000, 3000000]);
                } elseif ($priceRange === 'tren-3-trieu') {
                    $q->where('price', '>', 3000000);
                }
            });
        }

        // Lọc theo diện tích
        if ($request->filled('dientich')) {
            $sizeRange = $request->input('dientich');
            $query->whereHas('room', function ($q) use ($sizeRange) {
                if ($sizeRange === 'duoi-20') {
                    $q->where('area', '<', 20);
                } elseif ($sizeRange === '20-30') {
                    $q->whereBetween('area', [20, 30]);
                } elseif ($sizeRange === '30-50') {
                    $q->whereBetween('area', [30, 50]);
                } elseif ($sizeRange === 'tren-50') {
                    $q->where('area', '>', 50);
                }
            });
        }

        // Lọc theo mảng tiện ích
        if ($request->filled('amenities') && is_array($request->input('amenities'))) {
            $amenityIds = $request->input('amenities');
            $amenityNames = Amenity::whereIn('id', $amenityIds)->pluck('name')->toArray();

            $query->whereHas('room', function ($q) use ($amenityNames) {
                foreach ($amenityNames as $name) {
                    $q->where('amenities', 'like', "%{$name}%");
                }
            });
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    /**
     * Xử lý tạo lịch hẹn và gửi thông báo cho chủ trọ
     */
    public function createAppointment($id, array $data)
    {
        $post = RoomPost::with('room.boardingHouse')->findOrFail($id);
        $landlordId = $post->room->boardingHouse->user_id ?? $post->landlord_id;

        $appointment = Appointment::create([
            'user_id' => Auth::id(), // Đã sửa lỗi usser_id
            'landlord_id' => $landlordId,
            'room_id' => $post->room_id,
            'date' => $data['date'],
            'time' => $data['time'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'notified' => false,
        ]);

        $landlord = $appointment->landlord;
        if ($landlord) {
            $landlord->notify(new NewAppointment($appointment));
        }

        return $appointment;
    }
}
?>