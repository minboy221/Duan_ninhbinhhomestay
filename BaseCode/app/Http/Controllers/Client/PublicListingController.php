<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\RoomPost;
use Illuminate\Http\Request;
use Inertia\Inertia;


class PublicListingController extends Controller
{
    //Phần hiển thị danh sách tin đăng của phòng trọ công khai
    public function index(Request $request)
    {
        //chỉ lấy các tin đăng đã được phê duyệt
        $query = RoomPost::with(['room.floor.boardingHouse'])
            ->where('status', 'approved')
            ->latest();

        //bộ lọc tìm kiếm nhanh theo tiêu đề tin đăng
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        //phân trang
        $listings = $query->paginate(10)->withQueryString();
        return Inertia::render('Client/Listings/Index', [
            'listings' => $listings,
            'filters' => $request->only(['search'])
        ]);
    }
}
