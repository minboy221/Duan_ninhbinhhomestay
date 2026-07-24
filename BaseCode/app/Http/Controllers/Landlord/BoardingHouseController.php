<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\BoardingHouseService;
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

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'address_detail' => 'required|string|max:500',
            'directions_guide' => 'nullable|string|max:1000',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'room_images' => 'required|array',
            'room_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

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
}
