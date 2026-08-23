<?php

namespace App\Http\Controllers;
use App\Models\RoomPost;
use App\Notifications\NewAppointment;
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
        $room = Room::where('id', $request->room_id)
        ->whereHas('boardingHouse', function($q){
            $q->where('user_id', auth()->id());
        })->first();
        if(!$room){
            return redirect()->back()->with('error','Phòng trọ được chọn không hợp lệ hoặc không thuộc quyền sở hữu của bạn!');
        }
        // Check phòng có bị đóng băng không
        if ($user->isRoomFrozen($room)) {
            return redirect()->back()->with('error', 'Phòng này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ. Vui lòng nâng cấp gói để đăng tin cho thuê!');
        }
        // Đếm số lượng tin đăng công khai / pending bên chủ trọ
        $currentListingsCount = \App\Models\RoomPost::where('landlord_id', $user->id)
            ->whereIn('status', ['published', 'approved', 'pending'])
            ->count();
        // Check hạn mức max_listings của gói hiện tại
        if (!$user->canCreateResource('max_listings', $currentListingsCount)) {
            $limit = $user->getFeatureValue('max_listings');
            return redirect()->back()->with(
                'error',
                "Gói dịch vụ hiện tại của bạn chỉ cho phép đăng tối đa {$limit} tin công khai. Vui lòng nâng cấp gói VIP để đăng thêm tin mới!"
            );
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
        //chặn check nếu phòng đã đẩy người
        if($post->room && $post->room->capacity > 0 && $post->room->current_people >= $post->room->capacity){
            return redirect()->route('landlord.listings.index')
            ->with('error','Phòng này hiện đã đủ số lượng người ở hệ thống từ chối cập nhật tin đăng!');
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

        $services = $room->services->map(function ($service) {
            if ($service->pivot && !is_null($service->pivot->price)) {
                $service->price = $service->pivot->price;
            }
            return $service;
        });

        return response()->json([
            'services' => $services,
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
        //chặn nếu phòng đã đủ số lượng người ở
        if ($post->room && $post->room->capacity > 0 && $post->room->current_people >= $post->room->capacity) {
            return redirect()->route('landlord.listings.index')
                ->with('error', 'Phòng trọ này hiện đã đủ người ở (đã lấp đầy). Bạn không thể chỉnh sửa tin đăng!');
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