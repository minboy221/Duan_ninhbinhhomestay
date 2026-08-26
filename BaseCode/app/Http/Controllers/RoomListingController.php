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
        return Inertia::render('Landlord/Listings/index', [
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

        //bảo mật nếu tin đăng là công khai thì sẽ không cho xoá
        if($post->status === 'approved'){
            return redirect()->back()
            ->with('error','hệ thống từ chối,bạn không thể xoá tin đăng ở trạng thái công khai');
        }
        $this->roomPostService->deletePost($post);
        return redirect()->route('landlord.listings.index')
            ->with('success', 'Đã xoá bài đăng thành công!');
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
        $post->update(['status' => 'closed']);
        return redirect()->route('landlord.listings.index')
            ->with('success', 'Đã đóng tin đăng thành công! tin đăng đã được gỡ bỏ');
    }

    // Đẩy tin đăng lên đầu trang
    public function bump($id)
    {
        $post = RoomPost::findOrFail($id);
        if ($post->landlord_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền đẩy tin đăng này');
        }

        if ($post->status !== 'approved') {
            return redirect()->back()->with('error', 'Chỉ có thể đẩy những tin đang được hiển thị!');
        }

        $user = auth()->user();
        if ($user->bump_credits <= 0) {
            return redirect()->back()->with('error', 'Bạn đã hết lượt đẩy tin! Vui lòng mua thêm gói.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($post, $user) {
            $post->update([
                'bumped_at' => now(),
                'bump_count' => $post->bump_count + 1
            ]);

            $user->update([
                'bump_credits' => $user->bump_credits - 1
            ]);
        });

        return redirect()->route('landlord.listings.index')
            ->with('success', 'Đã đẩy tin thành công! Tin đăng của bạn đã được đưa lên đầu trang.');
    }

    // Mua gói đẩy tin (giả lập)
    public function buyPackage(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'package' => 'required|in:standard,premium,vip'
        ]);

        $package = $request->input('package');
        $credits = 0;
        $packageName = '';

        switch ($package) {
            case 'standard':
                $credits = 10;
                $packageName = 'Gói Cơ bản (10 lượt)';
                break;
            case 'premium':
                $credits = 30;
                $packageName = 'Gói Phổ thông (30 lượt)';
                break;
            case 'vip':
                $credits = 100;
                $packageName = 'Gói Đặc quyền (100 lượt)';
                break;
        }

        $user = auth()->user();
        $user->update([
            'bump_credits' => $user->bump_credits + $credits,
            'package_name' => $packageName
        ]);

        return redirect()->route('landlord.listings.index')
            ->with('success', "Đã thanh toán giả lập thành công! Bạn được cộng {$credits} lượt đẩy tin.");
    }
}