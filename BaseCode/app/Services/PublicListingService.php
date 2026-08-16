<?php

namespace App\Services;

use App\Models\RoomPost;
use App\Models\Amenity;
use App\Models\Area;
use App\Models\Category;
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
    protected $aiSearchService;

    public function __construct(
        RoomPostRepositoryInterface $roomPostRepository,
        ReviewRepositoryInterface $reviewRepository,
        AiRoomSearchService $aiSearchService
    ) {
        $this->roomPostRepository = $roomPostRepository;
        $this->reviewRepository = $reviewRepository;
        $this->aiSearchService = $aiSearchService;
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
     * Xử lý bộ lọc nâng cao kết hợp AI Text-to-Filter cho danh sách tin đăng công khai
     */
    public function getFilteredListings(Request $request)
    {
        $aiParsed = null;
        $aiPrompt = $request->input('ai_prompt');

        if ($request->filled('ai_prompt')) {
            $aiParsed = $this->aiSearchService->parseSearchPrompt($aiPrompt);
        }

        $query = RoomPost::with(['room.boardingHouse', 'room.floor', 'landlord'])
            ->where('status', 'approved');

        // 1. Tìm kiếm theo từ khóa (Keyword / Title / Description / Address)
        $search = $request->input('search') ?: ($aiParsed['keyword'] ?? null);
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('room.boardingHouse', function ($bq) use ($search) {
                      $bq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('address_detail', 'like', '%' . $search . '%')
                         ->orWhere('district', 'like', '%' . $search . '%');
                  });
            });
        }

        // 2. Lọc theo khu vực (Area)
        $areaId = $request->input('area_id') ?: ($aiParsed['area_id'] ?? null);
        if ($areaId) {
            $area = Area::find($areaId);
            if ($area) {
                $query->whereHas('room.boardingHouse', function ($q) use ($area) {
                    $q->where('address_detail', 'like', "%{$area->name}%")
                      ->orWhere('district', 'like', "%{$area->name}%");
                });
            }
        }

        // 3. Lọc theo khoảng giá (Price Range hoặc Numeric Min/Max)
        $priceRange = $request->input('price');
        $priceMin = $request->input('price_min') ?: ($aiParsed['price_min'] ?? null);
        $priceMax = $request->input('price_max') ?: ($aiParsed['price_max'] ?? null);

        if ($priceRange) {
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
        } elseif ($priceMin !== null || $priceMax !== null) {
            $query->whereHas('room', function ($q) use ($priceMin, $priceMax) {
                if ($priceMin !== null && $priceMax !== null) {
                    $q->whereBetween('price', [$priceMin, $priceMax]);
                } elseif ($priceMin !== null) {
                    $q->where('price', '>=', $priceMin);
                } elseif ($priceMax !== null) {
                    $q->where('price', '<=', $priceMax);
                }
            });
        }

        // 4. Lọc theo diện tích (Size Range hoặc Numeric Min/Max)
        $sizeRange = $request->input('dientich');
        $areaMin = $request->input('area_min') ?: ($aiParsed['area_min'] ?? null);
        $areaMax = $request->input('area_max') ?: ($aiParsed['area_max'] ?? null);

        if ($sizeRange) {
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
        } elseif ($areaMin !== null || $areaMax !== null) {
            $query->whereHas('room', function ($q) use ($areaMin, $areaMax) {
                if ($areaMin !== null && $areaMax !== null) {
                    $q->whereBetween('area', [$areaMin, $areaMax]);
                } elseif ($areaMin !== null) {
                    $q->where('area', '>=', $areaMin);
                } elseif ($areaMax !== null) {
                    $q->where('area', '<=', $areaMax);
                }
            });
        }

        // 5. Lọc theo tầng (Floor number)
        $floorNumber = $request->input('floor') ?: ($aiParsed['floor_number'] ?? null);
        if ($floorNumber !== null) {
            $query->whereHas('room.floor', function ($q) use ($floorNumber) {
                $q->where('sort_order', $floorNumber)
                  ->orWhere('name', 'like', "%Tầng {$floorNumber}%")
                  ->orWhere('name', 'like', "%Tầng {$floorNumber}")
                  ->orWhere('name', 'like', "{$floorNumber}");
            });
        }

        // 6. Lọc theo Loại phòng (Categories)
        $categoryIds = $request->input('categories');
        if (empty($categoryIds) && !empty($aiParsed['category_id'])) {
            $categoryIds = [$aiParsed['category_id']];
        }
        if (!empty($categoryIds) && is_array($categoryIds)) {
            $categoryNames = Category::whereIn('id', $categoryIds)->pluck('name')->toArray();
            if (!empty($categoryNames)) {
                $query->where(function ($q) use ($categoryNames) {
                    foreach ($categoryNames as $catName) {
                        $q->orWhere('title', 'like', "%{$catName}%")
                          ->orWhere('description', 'like', "%{$catName}%");
                    }
                });
            }
        }

        // 7. Lọc theo Tiện ích (Amenities)
        $amenityIds = $request->input('amenities');
        if (empty($amenityIds) && !empty($aiParsed['amenity_ids'])) {
            $amenityIds = $aiParsed['amenity_ids'];
        }
        if (!empty($amenityIds) && is_array($amenityIds)) {
            $amenityNames = Amenity::whereIn('id', $amenityIds)->pluck('name')->toArray();
            if (!empty($amenityNames)) {
                $query->where(function ($mainQ) use ($amenityNames) {
                    foreach ($amenityNames as $name) {
                        $mainQ->where(function ($subQ) use ($name) {
                            $subQ->where('description', 'like', "%{$name}%")
                                 ->orWhere('title', 'like', "%{$name}%")
                                 ->orWhereHas('room.services', function ($sq) use ($name) {
                                     $sq->where('name', 'like', "%{$name}%");
                                 });
                        });
                    }
                });
            }
        }

        $paginatedListings = $query->latest()->paginate(10)->withQueryString();

        return [
            'listings' => $paginatedListings,
            'ai_parsed' => $aiParsed,
        ];
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