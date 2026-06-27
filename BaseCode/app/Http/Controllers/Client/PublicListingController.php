<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\RoomPost;
use Illuminate\Http\Request;
use Inertia\Inertia;


class PublicListingController extends Controller
{
    //Phần hiển thị danh sách tin đăng của phòng trọ công khai
    public function index(Request $request, \App\Services\CategoryService $categoryService)
    {
        //chỉ lấy các tin đăng đã được phê duyệt, kèm theo thông tin phòng và người đăng
        $query = RoomPost::with(['room.boardingHouse', 'landlord'])
            ->where('status', 'approved')
            ->latest();

        //bộ lọc tìm kiếm nhanh theo tiêu đề tin đăng
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        //phân trang
        $listings = $query->paginate(10)->withQueryString();
        $categoryData = $categoryService->getActiveData();

        return Inertia::render('Client/timtro', [
            'listings' => $listings,
            'filters' => $request->only(['search']),
            'categories' => $categoryData['types'],
            'areas' => $categoryData['areas'],
            'amenities' => $categoryData['amenities'],
        ]);
    }

    // Phần hiển thị chi tiết 1 tin đăng phòng trọ
    public function show(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            abort(404, 'Không tìm thấy ID bài đăng');
        }

        $post = RoomPost::with(['room.boardingHouse', 'room.services', 'landlord'])
            ->where('id', $id)
            ->where('status', 'approved')
            ->firstOrFail();

        // Tăng lượt xem (không bắt buộc nhưng tốt cho SEO/thống kê)
        $post->increment('view_count');

        // Lấy danh sách tin đăng tương tự (cùng khu vực, hoặc random) - loại trừ tin hiện tại
        $similarPosts = RoomPost::with(['room.boardingHouse'])
            ->where('id', '!=', $id)
            ->where('status', 'approved')
            ->inRandomOrder()
            ->take(3)
            ->get();

        // Tạm thời giả lập dữ liệu bình luận (chưa có model Review cho boarding_house/room_post)
        $reviews = [];
        $averageRating = 0; // Chưa có đánh giá

        return Inertia::render('Client/chitiettro', [
            'post' => $post,
            'similarPosts' => $similarPosts,
            'reviews' => $reviews,
            'averageRating' => $averageRating
        ]);
    }
}
