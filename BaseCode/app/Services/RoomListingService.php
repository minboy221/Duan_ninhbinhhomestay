<?php
namespace App\Services;

use App\Models\RoomPost;
use App\Notifications\RoomPostStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PharIo\Manifest\License;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\RoomPostRepositoryInterface;
use App\Repositories\Interfaces\ReviewRepositoryInterface;

class RoomListingService
{
    protected $roomPostRepository;
    protected $reviewRepository;

    public function __construct(
        RoomPostRepositoryInterface $roomPostRepository,
        ReviewRepositoryInterface $reviewRepository
    ) {
        $this->roomPostRepository = $roomPostRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function getPostDetailsForLandlord(int $postId, int $landlordId)
    {
        $post = $this->roomPostRepository->getPostForLandlord($postId, $landlordId);
        $reviews = $this->reviewRepository->getReviewsByRoomId($post->room_id)->map(function ($r) {
            return [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
                'tenant_name' => $r->tenant->name ?? 'Người dùng ẩn danh',
                'tenant_avatar' => $r->tenant->avatar ?? null,
            ];
        });
        
        $averageRating = $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : 0;
        $uniqueViews = $this->roomPostRepository->countUniqueViews($post->id);

        return [
            'post' => $post,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'uniqueViews' => $uniqueViews,
        ];
    }

    //Phần lấy danh sách tất cả các tin đăng của chủ trọ để hiển thị
    public function getLandlordPosts(int $landlordId): LengthAwarePaginator
    {
        return RoomPost::where('landlord_id', $landlordId)
            ->with(['room.floor', 'room.boardingHouse'])  //load trước các bảng có mối quan hệ
            ->latest()
            ->paginate(10); //phân trang
    }

    //phần sử lý upload ảnh bài đăng
    public function uploadImages(array $files): array
    {

        // Phần sử lý upload danh sách hình ảnh bài đăng
        $uploadedImages = [];
        foreach ($files as $file) {
            $path = $file->store('room_posts_images', 'public');
            $uploadedImages[] = '/storage/' . $path;
        }
        return $uploadedImages;
    }

    // Phần thêm mới bài đăng của chủ trọ dưới dạng nháp hoặc chờ duyệt
    public function createPost(array $data, array $files, string $status): RoomPost
    {
        $imageUrls = !empty($files) ? $this->uploadImages($files) : [];
        //chạy transaction để đảm bảo an toàn cho DB
        $post = DB::transaction(function () use ($data, $imageUrls, $status) {

            $room = \App\Models\Room::find($data['room_id']);
            if ($room) {
                $roomUpdateData = [];
                if (isset($data['current_people'])) {
                    $roomUpdateData['current_people'] = (int)$data['current_people'];
                }
                if (isset($data['capacity'])) {
                    $roomUpdateData['capacity'] = (int)$data['capacity'];
                }
                if (!empty($roomUpdateData)) {
                    $room->update($roomUpdateData);
                }

                if ($room->boardingHouse) {
                    $room->boardingHouse()->update([
                        'address_detail' => $data['address'] ?? $room->boardingHouse->address_detail,
                        'latitude' => $data['latitude'] ?? $room->boardingHouse->latitude,
                        'longitude' => $data['longitude'] ?? $room->boardingHouse->longitude,
                    ]);
                }
            }
            return RoomPost::create([
                'landlord_id' => auth()->id(),
                'room_id' => $data['room_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $imageUrls,
                'status' => $status,//Nhận 'draft' hoặc 'pending' động từ Controller
                'view_count' => 0, //hiển thị lượt xem tin đăng
                'is_vip' => false,
            ]);
        });

        // Gửi thông báo cho Admin nếu bài đăng ở trạng thái chờ duyệt
        if ($status === 'pending') {
            $admins = \App\Models\User::where('role', 'admin')->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewRoomPostNotification($post));
        }

        return $post;
    }

    //Phần sửa tin đăng
    public function updatePost(RoomPost $post, array $data, ?array $newFiles, string $status): bool
    {
        $updated = DB::transaction(function () use ($post, $data, $newFiles, $status) {
            $room = \App\Models\Room::find($data['room_id']);
            if ($room) {
                $roomUpdateData = [];
                if (isset($data['current_people'])) {
                    $roomUpdateData['current_people'] = (int)$data['current_people'];
                }
                if (isset($data['capacity'])) {
                    $roomUpdateData['capacity'] = (int)$data['capacity'];
                }
                if (!empty($roomUpdateData)) {
                    $room->update($roomUpdateData);
                }

                if ($room->boardingHouse) {
                    $room->boardingHouse->update([
                        'address_detail' => $data['address'] ?? $room->boardingHouse->address_detail,
                        'latitude' => $data['latitude'] ?? $room->boardingHouse->latitude,
                        'longitude' => $data['longitude'] ?? $room->boardingHouse->longitude,
                    ]);
                }
            }
            //lấy danh sách ảnh cũ được giữ lại từ Frontend gửi lên
            $imageUrls = $data['existing_images'] ?? [];
            //tìm các ảnh cũ bị người dùng xoá bỏ để dọn dẹp
            $oldImages = $post->image ?? [];
            $deletedImages = array_diff($oldImages, $imageUrls);
            foreach ($deletedImages as $url) {
                $path = str_replace('/storage/', '', $url);
                Storage::disk('public')->delete($path);
            }
            //nếu user đăng tải thêm ảnh mới
            if ($newFiles) {
                $newImageUrl = $this->uploadImages($newFiles);
                $imageUrls = array_merge($imageUrls, $newImageUrl);
            }
            // cập nhật thêm dữ liệu mới
            return $post->update([
                'room_id' => $data['room_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $imageUrls,
                'status' => $status, //chuyển trạng thái chờ duyệt hoặc bản nháp
            ]);
        });

        // Gửi thông báo cho Admin nếu bài đăng được gửi duyệt
        if ($updated && $status === 'pending') {
            $admins = \App\Models\User::where('role', 'admin')->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewRoomPostNotification($post));
        }

        return $updated;
    }

    //Phần xoá tin đăng
    public function deletePost(RoomPost $post): ?bool
    {
        return DB::transaction(function () use ($post) {
            //phần duyệt mảng ảnh tìm file để xoá trong storage
            if (!empty($post->image) && is_array($post->image)) {
                foreach ($post->image as $url) {
                    //chuyển đổi từ link public về đường dẫn gốc để xoá
                    $path = str_replace('/storage/', '', $url);
                    Storage::disk('public')->delete($path);
                }
            }
            //xoá dữ liệu đó trong bảng room_posts của db
            return $post->delete();
        });
    }

    // Phần Chức Năng Dành Cho Admin
    //admin phê duyệt tin đăng
    public function approvePost(RoomPost $post): bool
    {
        return DB::transaction(function () use ($post) {
            $updated = $post->update([
                'status' => 'approved', //chuyển trạng thái đã xác thực
                'reject_reason' => null, //xoá ký do từ chối cũ nếu có
                'published_at' => now(), //Ghi nhận thời gian xuất bản công khai khi admin duyệt
            ]);
            if ($updated && $post->landlord) {
                //kích hoạt thông báo tự động vào bảng notifications hiển thị cho chủ trọ
                $post->landlord->notify(new RoomPostStatusNotification($post, 'approved'));
            }
            return $updated;
        });
    }

    //Phần admin từ chối duyệt tin đăng kèm theo lý do
    public function rejectPost(RoomPost $post, string $reason): bool
    {
        return DB::transaction(function () use ($post, $reason) {
            $updated = $post->update([
                'status' => 'rejected',       // Chuyển trạng thái sang từ chối
                'reject_reason' => $reason,          // Ghi nhận lý do gõ từ popup admin vào trường reject_reason của bạn
                'published_at' => null,
            ]);

            if ($updated && $post->landlord) {
                // Kích hoạt bắn thông báo kèm lý do từ chối vào bảng notifications cho chủ trọ
                $post->landlord->notify(new RoomPostStatusNotification($post, 'rejected'));
            }
            return $updated;
        });
    }
}
?>