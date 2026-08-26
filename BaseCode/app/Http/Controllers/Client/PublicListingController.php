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
        $baseQuery = RoomPost::with(['room.boardingHouse', 'landlord'])
            ->where('status', 'approved');

        //bộ lọc tìm kiếm nhanh theo tiêu đề tin đăng
        if ($request->has('search')) {
            $baseQuery->where('title', 'like', '%' . $request->search . '%');
        }

        // 1. Lấy danh sách tin đã đẩy
        $bumpedPosts = (clone $baseQuery)
            ->whereNotNull('bumped_at')
            ->orderBy('bumped_at', 'desc')
            ->get();

        // 2. Lấy danh sách tin thường
        $regularPosts = (clone $baseQuery)
            ->whereNull('bumped_at')
            ->orderBy('published_at', 'desc')
            ->get();

        // 3. Thuật toán xen kẽ tin đã đẩy của các chủ trọ khác nhau (Interleaving)
        $bumpedByLandlord = [];
        foreach ($bumpedPosts as $post) {
            $bumpedByLandlord[$post->landlord_id][] = $post;
        }

        $interleavedBumped = [];
        $hasMore = true;
        $index = 0;
        while ($hasMore) {
            $hasMore = false;
            foreach ($bumpedByLandlord as $landlordId => $posts) {
                if (isset($posts[$index])) {
                    $interleavedBumped[] = $posts[$index];
                    $hasMore = true;
                }
            }
            $index++;
        }

        // 4. Trộn hai danh sách (tin đẩy xen kẽ đứng trước, tin thường đứng sau)
        $allPosts = array_merge($interleavedBumped, $regularPosts->all());

        // 5. Phân trang thủ công để tương thích với Vue/Inertia Paginator
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $currentItems = array_slice($allPosts, ($currentPage - 1) * $perPage, $perPage);

        $listings = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            count($allPosts),
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query()
            ]
        );

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

        if ($post->room && $post->room->services) {
            $post->room->services->map(function ($service) {
                if ($service->pivot && !is_null($service->pivot->price)) {
                    $service->price = $service->pivot->price;
                }
                return $service;
            });
        }

        // Tạm tắt tự động cập nhật timestamps
        $post->timestamps = false;
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
