<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BoardingHouse;
use Illuminate\Support\Facades\Auth;

class BoardingHouseController extends Controller
{
    public function index()
    {
        $boardingHouses = BoardingHouse::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return Inertia::render('Landlord/BoardingHouses/Index', [
            'boardingHouses' => $boardingHouses
        ]);
    }

    public function history()
    {
        $boardingHouses = BoardingHouse::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
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
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'room_images' => 'required|array',
            'room_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $contractImagesPath = [];
        if ($request->hasFile('contract_images')) {
            foreach ($request->file('contract_images') as $file) {
                $path = $file->store('boarding_houses/contracts', 'public');
                $contractImagesPath[] = $path;
            }
        }

        $roomImagesPath = [];
        if ($request->hasFile('room_images')) {
            foreach ($request->file('room_images') as $file) {
                $path = $file->store('boarding_houses/rooms', 'public');
                $roomImagesPath[] = $path;
            }
        }

        $house = BoardingHouse::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'district' => $request->district,
            'address_detail' => $request->address_detail,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'contract_images' => json_encode($contractImagesPath),
            'room_images' => json_encode($roomImagesPath),
            'status' => 'pending'
        ]);

        // Gửi thông báo cho Admin
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\AdminNotification(
                'Có cơ sở mới cần duyệt',
                'Chủ trọ ' . Auth::user()->name . ' vừa tạo thêm cơ sở mới: ' . $house->name,
                'new_boarding_house',
                '/admin/boarding-houses/' . $house->id
            ));
        }

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

        $stats = [
            'room_count' => $roomCount,
            'floor_count' => $floorCount,
            'post_count' => $postCount,
        ];

        return Inertia::render('Landlord/BoardingHouses/Show', [
            'house' => $house,
            'stats' => $stats
        ]);
    }
}
