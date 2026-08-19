<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\BoardingHouseService;
use App\Http\Requests\StoreBoardingHouseRequest;
use App\Http\Requests\UpdateBoardingHouseRequest;
use App\Models\BoardingHouse; // Still needed for selectBoardingHouse if not moved

class BoardingHouseController extends Controller
{
    protected $boardingHouseService;

    public function __construct(BoardingHouseService $boardingHouseService)
    {
        $this->boardingHouseService = $boardingHouseService;
    }

    public function index()
    {
        $boardingHouses = $this->boardingHouseService->getLandlordBoardingHouses(Auth::id());

        return Inertia::render('Landlord/BoardingHouses/Index', [
            'boardingHouses' => $boardingHouses
        ]);
    }

    public function history()
    {
        $boardingHouses = $this->boardingHouseService->getLandlordBoardingHousesHistory(Auth::id());

        return Inertia::render('Landlord/BoardingHouses/History', [
            'boardingHouses' => $boardingHouses
        ]);
    }

    public function create()
    {
        return Inertia::render('Landlord/BoardingHouses/Create');
    }

    public function store(StoreBoardingHouseRequest $request)
    {
        $currentCount = \App\Models\BoardingHouse::where('user_id', $user->id)->count();
        if (!$user->canCreateResource('max_boarding_houses', $currentCount)) {
            $limit = $user->getFeatureValue('max_boarding_houses');
            return redirect()->back()->with('error', "Gói dịch vụ của bạn cho phép tạo tối đa {$limit} Cơ sở/Dãy trọ. Bạn hiện đang có {$currentCount} Cơ sở. Vui lòng nâng cấp gói để tạo thêm!");
        }
        $this->boardingHouseService->createBoardingHouse(
            $request->only(['name', 'district', 'address_detail', 'directions_guide', 'latitude', 'longitude']),
            $request->file('room_images'),
            $request->file('contract_images'),
            Auth::id(),
            Auth::user()->name
        );
        return redirect()->route('landlord.boarding-houses.index')->with('success', 'Đã thêm cơ sở mới. Đang chờ Ban Quản Trị xét duyệt.');
    }

    public function selectBoardingHouse(Request $request)
    {
        $request->validate(['id' => 'required|exists:boarding_houses,id']);

        $house = BoardingHouse::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->where('status', 'approved')
            ->firstOrFail();

        session(['selected_boarding_house_id' => $house->id]);

        if ($request->has('redirect_to')) {
            return redirect($request->redirect_to)->with('success', 'Đã chuyển sang quản lý cơ sở: ' . $house->name);
        }

        return back()->with('success', 'Đã chuyển sang quản lý cơ sở: ' . $house->name);
    }

    public function show($id)
    {
        $house = BoardingHouse::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Thống kê đồng bộ với trang "Nhà & Phòng"
        $roomService = app(\App\Services\RoomService::class);
        $floors = $roomService->getFloorsWithRooms(Auth::id(), $house->id);

        $floorCount = count($floors);
        $roomCount = 0;
        $roomIds = [];

        foreach ($floors as $floor) {
            $roomCount += count($floor['rooms']);
            foreach ($floor['rooms'] as $room) {
                $roomIds[] = $room['id'];
            }
        }

        $postCount = \App\Models\RoomPost::whereIn('room_id', $roomIds)->count();
        $reviewCount = $house->reviews()->count();

        $stats = [
            'room_count' => $roomCount,
            'floor_count' => $floorCount,
            'post_count' => $postCount,
            'review_count' => $reviewCount,
        ];

        return Inertia::render('Landlord/BoardingHouses/Show', [
            'house' => $house,
            'stats' => $stats
        ]);
    }

    //phần sửa cơ sở
    public function edit($id)
    {
        $house = BoardingHouse::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        return Inertia::render('Landlord/BoardingHouses/Edit', [
            'house' => $house
        ]);
    }

    public function update(UpdateBoardingHouseRequest $request, $id)
    {
        try {
            $this->boardingHouseService->updateBoardingHouse(
                $id,
                $request->only(['name', 'district', 'address_detail', 'directions_guide', 'latitude', 'longitude']),
                $request->file('room_images'),
                $request->file('contract_images'),
                Auth::id()
            );
            return redirect()->route('landlord.boarding-houses.index')->with('success', 'Cập nhật cơ sở trọ thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    //phần xoá cơ sở
    public function destroy($id)
    {
        try {
            $this->boardingHouseService->deleteBoardingHouse($id, Auth::id());
            return redirect()->route('landlord.boarding-houses.index')->with('success', 'Xoá cơ sở trọ thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
