<?php

namespace App\Http\Controllers;


use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\RoomPost;
use App\Http\Requests\StoreRoomPostRequest;
use App\Http\Requests\UpdateRoomPostRequest;
use App\Services\RoomListingService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
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
        return Inertia::render('Landlord/Listings/Index', [
            'listings' => $listings
        ]);
    }

    // Phần hiển thị form nhập đăng tin
    public function create(): Response
    {
        $boardingHouses = BoardingHouse::where('user_id', auth()->id())
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
            ->with(['floors.rooms.roomPosts'])
            ->get();
        return Inertia::render('Landlord/Listings/Edit', [
            'post' => $post,
            'boardingHouses' => $boardingHouses
        ]);
    }
    //Phần xoá tin đăng
    public function destroy($id)
    {
        $post = RoomPost::findOrFail($id);

        if ($post->landlord_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền xoá bài đăng này');
        }
        $this->roomPostService->deletePost($post);
        return redirect()->route('landlord.listings.index')
            ->with('success', 'Đã xoá bài đăng thành công!');
    }
}