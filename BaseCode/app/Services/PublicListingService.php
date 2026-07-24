<?php

namespace App\Services;

use App\Models\RoomPost;
use App\Models\Amenity;
use App\Models\Area;
use App\Models\Appointment;
use App\Notifications\AppointmentStatusUpdated;
use App\Notifications\NewAppointment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\RoomPostRepositoryInterface;
use App\Repositories\Interfaces\ReviewRepositoryInterface;

class PublicListingService
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

    public function getPublicPostDetails(int $postId, ?int $userId, ?string $ipAddress)
    {
        $post = $this->roomPostRepository->getApprovedPost($postId);

        // Record unique view
        if (!$this->roomPostRepository->checkUniqueViewExists($postId, $userId, $ipAddress)) {
            $this->roomPostRepository->recordUniqueView($postId, $userId, $ipAddress);
            $this->roomPostRepository->incrementViewCount($postId);
        }

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

        $boardingHouse = $post->room->boardingHouse;
        $boardingHouseRating = $boardingHouse ? $boardingHouse->average_rating : 0;
        $boardingHouseReviewCount = $boardingHouse ? $boardingHouse->reviews()->count() : 0;

        return [
            'post' => $post,
            'room' => $post->room,
            'reviews' => $reviews,
            'boardingHouseRating' => $boardingHouseRating,
            'boardingHouseReviewCount' => $boardingHouseReviewCount,
        ];
    }

    /**
     * Xử lý bộ lọc nâng cao cho danh sách tin đăng công khai
     */
    public function getFilteredListings(Request $request)
    {
        $query = RoomPost::with(['room.boardingHouse', 'landlord'])
            ->where('status', 'approved');

        // Tìm kiếm theo tiêu đề
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Lọc theo khu vực
        if ($request->filled('area_id')) {
            $area = Area::find($request->input('area_id'));
            if ($area) {
                $query->whereHas('room.boardingHouse', function ($q) use ($area) {
                    $q->where('address_detail', 'like', "%{$area->name}%"); // Đã sửa lỗi address_detaill
                });
            }
        }

        // Lọc theo khoảng giá
        if ($request->filled('price')) {
            $priceRange = $request->input('price');
            $query->whereHas('room', function ($q) use ($priceRange) {
                if ($priceRange === 'duoi-1-trieu') {
                    $q->where('price', '<', 1000000);
                } elseif ($priceRange === '1-2-trieu') {
                    $q->whereBetween('price', [1000000, 2000000]);
                } elseif ($priceRange === '2-3-trieu') {
                    $q->whereBetween('price', [2000000, 3000000]);
                } elseif ($priceRange === 'tren-3-trieu') {
                    $q->where('price', '>', 3000000);
                }
            });
        }

        // Lọc theo diện tích
        if ($request->filled('dientich')) {
            $sizeRange = $request->input('dientich');
            $query->whereHas('room', function ($q) use ($sizeRange) {
                if ($sizeRange === 'duoi-20') {
                    $q->where('area', '<', 20);
                } elseif ($sizeRange === '20-30') {
                    $q->whereBetween('area', [20, 30]);
                } elseif ($sizeRange === '30-50') {
                    $q->whereBetween('area', [30, 50]);
                } elseif ($sizeRange === 'tren-50') {
                    $q->where('area', '>', 50);
                }
            });
        }

        // Lọc theo mảng tiện ích
        if ($request->filled('amenities') && is_array($request->input('amenities'))) {
            $amenityIds = $request->input('amenities');
            $amenityNames = Amenity::whereIn('id', $amenityIds)->pluck('name')->toArray();

            $query->whereHas('room', function ($q) use ($amenityNames) {
                foreach ($amenityNames as $name) {
                    $q->where('amenities', 'like', "%{$name}%");
                }
            });
        }

        return $query->latest()->paginate(10)->withQueryString();
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

    //Phần kiểm tra xem khung giờ đặt lịch của phòng có bị trung hay không
    public function isSlotOccupied(int $roomId, string $date, string $time): bool
    {
        return Appointment::where('room_id', $roomId)
            ->where('date', $date)
            ->where('time', $time)
            ->whereIn('status', ['pending', 'approved', 'confirmed'])
            ->exists();
    }

    //Phần xử lý lịch hẹn kèm theo lý do từ chủ trọ
    public function cancelAppointment($appointmentId, string $reason)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        //bảo mật check: đảm bảo người thực hiện đúng là chủ trọ của lịch hẹn này
        if ($appointment->landlord_id !== Auth::id()) {
            throw new Exception('Bạn không có quyền huỷ lịch hẹn này');
        }
        //không cho phép huỷ lịch đã hoàn thành hoặc đã bị huỷ từ trước
        if (in_array($appointment->status, ['cancelled', 'completed'])) {
            throw new Exception('Không thể chỉnh sửa lịch hẹn ở trạng thái này');
        }
        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ]);
        return $appointment;
    }

    //phần từ chối lịch hẹn kèm lý do từ chủ trọ
    public function rejectAppointmentWithReason(int $id, string $reason)
    {
        //đảm bảo chỉ chủ trọ sở hữu lịch hẹn này mới có quyền từ chối
        $appointment = Appointment::where('landlord_id', Auth::id())->findOrFail($id);
        if (in_array($appointment->status, ['rejected', 'cancelled', 'completed'])) {
            throw new \Exception('Lịch hẹn này đã được xử lý hoặc không thể từ chối');
        }
        //cập nhật trạng thái và lưu lý do
        $appointment->update([
            'status' => 'rejected',
            'cancellation_reason' => $reason
        ]);
        //gửi thông báo cập nhật trạng tháo cho người đã đặt lịch
        if ($appointment->user) {
            $appointment->user->notify(new AppointmentStatusUpdated($appointment));
        }
        return $appointment;
    }
}