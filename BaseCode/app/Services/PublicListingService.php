<?php

namespace App\Services;

use App\Models\RoomPost;
use App\Models\Amenity;
use App\Models\Area;
use App\Models\Category;
use App\Models\Appointment;
use App\Models\AiChatHistory;
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

        $reviews = $this->reviewRepository->getReviewsByRoomId($post->room_id)->map(function ($r) use ($post) {
            $isOwner = $post->room->boardingHouse && $r->tenant_id === $post->room->boardingHouse->user_id;
            $isAdmin = $r->tenant && $r->tenant->role === 'admin';
            return [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
                'tenant_name' => $r->tenant->name ?? 'Người dùng ẩn danh',
                'tenant_avatar' => $r->tenant->avatar ?? null,
                'is_admin' => $isAdmin,
                'is_owner' => $isOwner,
                'is_notice' => $isAdmin || $isOwner,
            ];
        })->sortByDesc('is_notice')->values();

        $boardingHouse = $post->room->boardingHouse;
        $boardingHouseRating = $boardingHouse ? $boardingHouse->average_rating : 0;
        $boardingHouseReviewCount = $boardingHouse ? $boardingHouse->realReviews()->count() : 0;

        return [
            'post' => $post,
            'room' => $post->room,
            'reviews' => $reviews,
            'boardingHouseRating' => $boardingHouseRating,
            'boardingHouseReviewCount' => $boardingHouseReviewCount,
        ];
    }

    public function getFeaturedRooms(int $limit = 6)
    {
        return $this->roomPostRepository->getFeaturedPosts($limit)->map(function ($post) {
            $room = $post->room;
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'image' => is_array($post->image) && count($post->image) > 0 ? $post->image[0] : null,
                'price' => $room ? $room->price : null,
                'area' => $room ? $room->area : null,
                'address' => $room && $room->boardingHouse ? $room->boardingHouse->address_detail : null,
                'isHot' => $post->view_count > 50, // Example logic
                'landlord_name' => $post->landlord ? $post->landlord->name : null,
                'landlord_avatar' => $post->landlord ? $post->landlord->avatar : null,
            ];
        });
    }

    public function getTopReviews(int $limit = 6)
    {
        return $this->reviewRepository->getTopReviews($limit)->map(function ($r) {
            return [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
                'tenant_name' => $r->tenant->name ?? 'Khách hàng',
                'tenant_avatar' => $r->tenant->avatar ?? null,
            ];
        });
    }

    public function getSystemStats()
    {
        $totalUsers = \App\Models\User::count();
        $totalLandlords = \App\Models\User::where('role', 'landlord')->count();
        $averageRating = \App\Models\Review::avg('rating') ?? 5.0;

        return [
            'totalUsers' => $totalUsers,
            'totalLandlords' => $totalLandlords,
            'averageRating' => round($averageRating, 1)
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

        // 2. Lọc theo loại phòng (Danh mục)
        if ($request->filled('category_id')) {
            $categoryIds = is_array($request->input('category_id')) ? $request->input('category_id') : [$request->input('category_id')];
            $query->whereHas('room', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            });
        }

        // 3. Lọc theo khu vực (Area)
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

        return [
            'listings' => $query->latest()->paginate(5)->withQueryString(),
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
        $userId = Auth::id();
        $note = $data['note'] ?? null;
        //tạo lịch hẹn xem phòng
        $appointment = Appointment::create([
            'user_id' => Auth::id(),
            'landlord_id' => $landlordId,
            'room_id' => $post->room_id,
            'date' => $data['date'],
            'time' => $data['time'],
            'note' => $note,
            'status' => 'pending',
            'notified' => false,
        ]);

        //gửi thông báo tới chủ trọ
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

    /**
     * Chuẩn hóa và kiểm tra đường dẫn ảnh phòng trọ, tự động fallback nếu ảnh không tồn tại
     */
    public function formatImageUrl($image): string
    {
        $defaultFallback = '/anh/phong1.jpg';
        if (empty($image)) return $defaultFallback;
        
        $firstImg = is_array($image) ? ($image[0] ?? '') : $image;
        if (empty($firstImg) || !is_string($firstImg)) return $defaultFallback;
        
        $trimmed = trim($firstImg);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }

        // Loại bỏ tiền tố private/ hoặc /storage/ để lấy tên file gốc
        $clean = preg_replace('/^(\/?storage\/|\/?private\/)+/i', '', $trimmed);
        
        // 1. Kiểm tra trong storage/app/public/
        if (file_exists(storage_path('app/public/' . $clean))) {
            return '/storage/' . $clean;
        }

        // 2. Kiểm tra trong public/storage/
        if (file_exists(public_path('storage/' . $clean))) {
            return '/storage/' . $clean;
        }

        // 3. Kiểm tra trong public/
        if (file_exists(public_path($clean))) {
            return '/' . $clean;
        }

        // 4. Nếu là file tồn tại trực tiếp trong public/anh/
        if (file_exists(public_path('anh/' . $clean))) {
            return '/anh/' . $clean;
        }

        return $defaultFallback;
    }

    /**
     * Tìm kiếm và định dạng kết quả cho Trợ lý AI Chatbot
     * - Ưu tiên dữ liệu phòng trọ mới nhất từ CSDL (status approved, available)
     * - Trả về đúng 2 kết quả có độ tương thích cao nhất & sát nhất với prompt
     * - Tự động xóa lịch sử quá 7 ngày và lưu tin nhắn cho tài khoản đăng nhập
     */
    public function searchRoomsForChatAssistant(string $prompt, ?int $userId = null): array
    {
        $prompt = trim($prompt);
        if (empty($prompt)) {
            return [
                'success' => true,
                'message' => 'Chào bạn! 👋 Mình là trợ lý AI của Ninh Bình HomeStay. Bạn muốn tìm phòng trọ như thế nào? (Ví dụ: phòng tầng 1 gần Hoa Lư dưới 2.5tr có gác xép...) ✨',
                'rooms' => [],
                'total_matches' => 0,
                'ai_parsed' => null,
                'suggestions' => [
                    '🏢 Tầng 1 Hoa Lư < 2.5tr',
                    '🌿 Studio gác xép nuôi pet',
                    '❄️ Có điều hòa & máy giặt',
                    '👥 Phòng ghép sinh viên',
                ],
            ];
        }

        // Tự động dọn dẹp tin nhắn quá 7 ngày của tài khoản nếu đã đăng nhập
        if ($userId) {
            AiChatHistory::where('user_id', $userId)
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
        }

        // 1. Phân tích ngữ nghĩa prompt
        $aiParsed = $this->aiSearchService->parseSearchPrompt($prompt);

        // 2. Nếu câu hỏi không liên quan đến tìm phòng / thuê trọ -> Từ chối trả lời ngay
        if (isset($aiParsed['is_related_to_room_search']) && $aiParsed['is_related_to_room_search'] === false) {
            $refusalMessage = $aiParsed['refusal_message'] ?? 'Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.';
            $suggestions = [
                '🏢 Tìm phòng tầng 1 quanh khu Hoa Lư',
                '❄️ Phòng có điều hòa, nóng lạnh dưới 3 triệu',
                '🌿 Studio gác xép cho nuôi thú cưng',
                '👥 Phòng ghép sinh viên giá rẻ',
            ];

            if ($userId) {
                AiChatHistory::create([
                    'user_id' => $userId,
                    'sender' => 'user',
                    'message' => $prompt,
                ]);
                AiChatHistory::create([
                    'user_id' => $userId,
                    'sender' => 'ai',
                    'message' => $refusalMessage,
                    'rooms_data' => [],
                    'ai_parsed' => $aiParsed,
                    'suggestions' => $suggestions,
                ]);
            }

            return [
                'success' => true,
                'message' => $refusalMessage,
                'ai_parsed' => $aiParsed,
                'rooms' => [],
                'total_matches' => 0,
                'suggestions' => $suggestions,
                'original_prompt' => $prompt,
            ];
        }

        // Hàm kiểm tra phòng hợp lệ (chỉ hiển thị phòng có tin đăng, không bảo trì/tạm ngưng, và chưa full người)
        $isEligiblePost = function ($post) {
            if (!$post || !$post->room) return false;
            $room = $post->room;
            
            // Loại bỏ phòng đang bảo trì, tạm ngưng hoặc đang xây
            if (in_array($room->status, ['maintenance', 'suspended', 'under_construction'])) {
                return false;
            }
            
            $capacity = max(1, (int) ($room->capacity ?? 1));
            $currentPeople = (int) ($room->current_people ?? 0);
            
            // Nếu phòng đã cho thuê và full người (hoặc bất kỳ phòng nào đã đủ số người tối đa) -> KHÔNG HIỆN
            if ($currentPeople >= $capacity) {
                return false;
            }
            
            return true;
        };

        // 3. Xây dựng truy vấn lọc chính xác theo các tiêu chí AI bóc tách (chỉ lấy tin đăng approved và có phòng hợp lệ)
        $query = RoomPost::with(['room.boardingHouse', 'room.floor', 'room.services', 'room.contracts', 'landlord'])
            ->where('status', 'approved')
            ->whereHas('room', function ($rq) {
                $rq->whereNotIn('status', ['maintenance', 'suspended', 'under_construction']);
            });

        $areaId = $aiParsed['area_id'] ?? null;
        $areaName = $aiParsed['area_name'] ?? null;
        $keyword = $aiParsed['keyword'] ?? null;
        $priceMin = $aiParsed['price_min'] ?? null;
        $priceMax = $aiParsed['price_max'] ?? null;
        $areaMin = $aiParsed['area_min'] ?? null;
        $areaMax = $aiParsed['area_max'] ?? null;
        $floorNumber = $aiParsed['floor_number'] ?? null;
        $categoryId = $aiParsed['category_id'] ?? null;
        $categoryName = $aiParsed['category_name'] ?? null;
        $amenityIds = $aiParsed['amenity_ids'] ?? [];
        $amenityNames = $aiParsed['amenity_names'] ?? [];

        // A. Lọc theo Từ khóa (Keyword / Landmark / Tên đường)
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->orWhere('description', 'like', '%' . $keyword . '%')
                  ->orWhereHas('room.boardingHouse', function ($bq) use ($keyword) {
                      $bq->where('name', 'like', '%' . $keyword . '%')
                         ->orWhere('address_detail', 'like', '%' . $keyword . '%')
                         ->orWhere('district', 'like', '%' . $keyword . '%');
                  });
            });
        }

        // B. Lọc theo Khu vực (Area)
        if ($areaId) {
            $area = Area::find($areaId);
            if ($area) {
                $query->whereHas('room.boardingHouse', function ($q) use ($area) {
                    $q->where('address_detail', 'like', "%{$area->name}%")
                      ->orWhere('district', 'like', "%{$area->name}%");
                });
            }
        } elseif (!empty($areaName)) {
            $cleanArea = preg_replace('/^(Phường|Xã|Thị trấn|Huyện|Thành phố)\s+/ui', '', $areaName);
            $query->where(function ($q) use ($areaName, $cleanArea) {
                $q->whereHas('room.boardingHouse', function ($bq) use ($areaName, $cleanArea) {
                    $bq->where('address_detail', 'like', "%{$areaName}%")
                       ->orWhere('district', 'like', "%{$areaName}%")
                       ->orWhere('address_detail', 'like', "%{$cleanArea}%")
                       ->orWhere('district', 'like', "%{$cleanArea}%");
                })
                ->orWhere('title', 'like', "%{$cleanArea}%");
            });
        }

        // C. Lọc theo Mức giá (Price Range)
        if ($priceMin !== null || $priceMax !== null) {
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

        // D. Lọc theo Diện tích (Area Size)
        if ($areaMin !== null || $areaMax !== null) {
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

        // E. Lọc theo Tầng (Floor Number)
        if ($floorNumber !== null) {
            $query->whereHas('room.floor', function ($q) use ($floorNumber) {
                $q->where('sort_order', $floorNumber)
                  ->orWhere('name', 'like', "%Tầng {$floorNumber}%")
                  ->orWhere('name', 'like', "%Tầng {$floorNumber}")
                  ->orWhere('name', 'like', "{$floorNumber}");
            });
        }

        // F. Lọc theo Loại phòng (Category)
        if ($categoryId) {
            $categoryNames = Category::where('id', $categoryId)->pluck('name')->toArray();
            if (!empty($categoryNames)) {
                $query->where(function ($q) use ($categoryNames) {
                    foreach ($categoryNames as $catName) {
                        $q->orWhere('title', 'like', "%{$catName}%")
                          ->orWhere('description', 'like', "%{$catName}%");
                    }
                });
            }
        } elseif (!empty($categoryName)) {
            $query->where(function ($q) use ($categoryName) {
                $q->where('title', 'like', "%{$categoryName}%")
                  ->orWhere('description', 'like', "%{$categoryName}%");
            });
        }

        // G. Lọc theo Tiện ích (Amenities)
        if (!empty($amenityIds) && is_array($amenityIds)) {
            $amenityDbNames = Amenity::whereIn('id', $amenityIds)->pluck('name')->toArray();
            if (!empty($amenityDbNames)) {
                $query->where(function ($mainQ) use ($amenityDbNames) {
                    foreach ($amenityDbNames as $name) {
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
        } elseif (!empty($amenityNames) && is_array($amenityNames)) {
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

        // 4. Thực thi truy vấn kết quả khớp chuẩn
        $matchingPosts = $query->get()->filter($isEligiblePost);
        $totalMatches = $matchingPosts->count();
        $isBudgetFriendly = !empty($aiParsed['is_budget_friendly']);

        // Xác định mức giá mục tiêu nếu người dùng có lọc giá
        $targetPrice = null;
        if ($priceMin !== null && $priceMax !== null) {
            $targetPrice = ($priceMin + $priceMax) / 2;
        } elseif ($priceMax !== null) {
            $targetPrice = $priceMax;
        } elseif ($priceMin !== null) {
            $targetPrice = $priceMin;
        }

        if ($totalMatches > 0) {
            // Sắp xếp các phòng khớp chuẩn
            $sortedPosts = $matchingPosts->sort(function ($a, $b) use ($isBudgetFriendly, $targetPrice) {
                $priceA = (float) ($a->room?->price ?? PHP_INT_MAX);
                $priceB = (float) ($b->room?->price ?? PHP_INT_MAX);

                // Ưu tiên 1: Nếu người dùng tìm "giá rẻ", "sinh viên", "tiết kiệm" -> Xếp giá thấp nhất lên đầu
                if ($isBudgetFriendly) {
                    if ($priceA !== $priceB) {
                        return $priceA <=> $priceB;
                    }
                } elseif ($targetPrice !== null) {
                    // Nếu tìm theo mức giá cụ thể -> Xếp khoảng cách giá sát nhất lên đầu
                    $diffA = abs($priceA - $targetPrice);
                    $diffB = abs($priceB - $targetPrice);
                    if ($diffA !== $diffB) {
                        return $diffA <=> $diffB;
                    }
                }

                // Ưu tiên 2: Còn phòng trống hoàn toàn (current_people == 0)
                $aEmpty = ($a->room && (int) $a->room->current_people === 0) ? 1 : 0;
                $bEmpty = ($b->room && (int) $b->room->current_people === 0) ? 1 : 0;
                if ($aEmpty !== $bEmpty) {
                    return $bEmpty <=> $aEmpty;
                }

                // Ưu tiên 3: Giá thấp hơn
                if ($priceA !== $priceB) {
                    return $priceA <=> $priceB;
                }

                // Ưu tiên 4: Cập nhật mới nhất
                $aTime = $a->updated_at ? $a->updated_at->timestamp : 0;
                $bTime = $b->updated_at ? $b->updated_at->timestamp : 0;
                return $bTime <=> $aTime;
            });
            $top2Posts = $sortedPosts->take(2);
        } else {
            // Không tìm thấy phòng khớp hoàn toàn -> Lấy các bài đăng đã duyệt, phòng chưa full để gợi ý
            $allApprovedPosts = RoomPost::with(['room.boardingHouse', 'room.floor', 'room.services', 'room.contracts', 'landlord'])
                ->where('status', 'approved')
                ->whereHas('room', function ($rq) {
                    $rq->whereNotIn('status', ['maintenance', 'suspended', 'under_construction']);
                })
                ->get()
                ->filter($isEligiblePost);

            if ($isBudgetFriendly) {
                // SẮP XẾP THEO GIÁ RẺ NHẤT TOÀN HỆ THỐNG
                $sortedPosts = $allApprovedPosts->sort(function ($a, $b) {
                    $priceA = (float) ($a->room?->price ?? PHP_INT_MAX);
                    $priceB = (float) ($b->room?->price ?? PHP_INT_MAX);
                    if ($priceA !== $priceB) {
                        return $priceA <=> $priceB;
                    }
                    $aEmpty = ($a->room && (int) $a->room->current_people === 0) ? 1 : 0;
                    $bEmpty = ($b->room && (int) $b->room->current_people === 0) ? 1 : 0;
                    if ($aEmpty !== $bEmpty) {
                        return $bEmpty <=> $aEmpty;
                    }
                    $aTime = $a->updated_at ? $a->updated_at->timestamp : 0;
                    $bTime = $b->updated_at ? $b->updated_at->timestamp : 0;
                    return $bTime <=> $aTime;
                });
                $top2Posts = $sortedPosts->take(2);
            } elseif ($targetPrice !== null) {
                // Nếu có mức giá trần hoặc khoảng giá: Ưu tiên gợi ý các phòng NẰM TRONG NGÂN SÁCH trước
                $withinBudgetPosts = $allApprovedPosts->filter(function ($p) use ($priceMin, $priceMax) {
                    $pPrice = (float) ($p->room?->price ?? PHP_INT_MAX);
                    if ($priceMax !== null && $pPrice > $priceMax) return false;
                    if ($priceMin !== null && $pPrice < $priceMin) return false;
                    return true;
                });

                $postsPool = ($withinBudgetPosts->count() > 0) ? $withinBudgetPosts : $allApprovedPosts;

                // SẮP XẾP THEO KHOẢNG CÁCH GIÁ SÁT NHẤT (|room.price - targetPrice| nhỏ nhất)
                $sortedByProximity = $postsPool->sort(function ($a, $b) use ($targetPrice) {
                    $priceA = (float) ($a->room?->price ?? PHP_INT_MAX);
                    $priceB = (float) ($b->room?->price ?? PHP_INT_MAX);
                    $diffA = abs($priceA - $targetPrice);
                    $diffB = abs($priceB - $targetPrice);

                    if ($diffA !== $diffB) {
                        return $diffA <=> $diffB;
                    }

                    $aEmpty = ($a->room && (int) $a->room->current_people === 0) ? 1 : 0;
                    $bEmpty = ($b->room && (int) $b->room->current_people === 0) ? 1 : 0;
                    if ($aEmpty !== $bEmpty) {
                        return $bEmpty <=> $aEmpty;
                    }

                    $aTime = $a->updated_at ? $a->updated_at->timestamp : 0;
                    $bTime = $b->updated_at ? $b->updated_at->timestamp : 0;
                    return $bTime <=> $aTime;
                });
                $top2Posts = $sortedByProximity->take(2);
            } else {
                // Nếu không có điều kiện giá: Ưu tiên phòng trống -> Giá rẻ hơn -> Mới nhất
                $sortedPosts = $allApprovedPosts->sort(function ($a, $b) {
                    $aEmpty = ($a->room && (int) $a->room->current_people === 0) ? 1 : 0;
                    $bEmpty = ($b->room && (int) $b->room->current_people === 0) ? 1 : 0;
                    if ($aEmpty !== $bEmpty) {
                        return $bEmpty <=> $aEmpty;
                    }
                    $priceA = (float) ($a->room?->price ?? PHP_INT_MAX);
                    $priceB = (float) ($b->room?->price ?? PHP_INT_MAX);
                    if ($priceA !== $priceB) {
                        return $priceA <=> $priceB;
                    }
                    $aTime = $a->updated_at ? $a->updated_at->timestamp : 0;
                    $bTime = $b->updated_at ? $b->updated_at->timestamp : 0;
                    return $bTime <=> $aTime;
                });
                $top2Posts = $sortedPosts->take(2);
            }
        }

        $mapRoomData = function ($post) use ($totalMatches, $isBudgetFriendly) {
            $room = $post->room;
            $house = $room?->boardingHouse;
            $capacity = max(1, (int) ($room?->capacity ?? 1));
            $currentPeople = (int) ($room?->current_people ?? 0);
            $hasResidents = ($currentPeople > 0);

            // Xác định nhãn trạng thái phòng
            $status = $room?->status ?? 'available';
            if ($hasResidents) {
                $statusLabel = "Đã có {$currentPeople} người ở";
            } else {
                $statusLabel = match($status) {
                    'available' => 'Còn phòng',
                    'rented' => 'Đã thuê',
                    'deposited' => 'Đã cọc',
                    'expiring_soon' => 'Sắp hết hạn',
                    'pending_renewal' => 'Chờ gia hạn',
                    default => 'Còn phòng'
                };
            }

            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug_with_hash,
                'url' => route('chitiettro', $post->slug_with_hash, false),
                'price' => $room?->price ?? 0,
                'price_formatted' => number_format($room?->price ?? 0, 0, ',', '.') . ' đ/tháng',
                'area' => $room?->area ?? null,
                'capacity' => $capacity,
                'current_people' => $currentPeople,
                'has_residents' => $hasResidents,
                'residents_label' => $hasResidents ? "Đã có {$currentPeople} người ở" : null,
                'address' => $house?->address_detail ?: ($house?->district ?: 'Ninh Bình'),
                'image' => $this->formatImageUrl($post->image),
                'status' => $status,
                'status_label' => $statusLabel,
                'floor' => $room?->floor?->name ?? null,
                'rating' => $house?->average_rating ?? null,
                'landlord_name' => $post->landlord?->name ?? 'Chủ trọ',
                'is_closest_match' => true,
                'badge_label' => $totalMatches > 0 ? ($isBudgetFriendly ? 'Giá rẻ nhất' : 'Phù hợp nhất') : 'Giá sát nhất',
            ];
        };

        $rooms = $top2Posts->map($mapRoomData)->values()->toArray();

        // Gợi ý follow-up prompts
        $suggestions = [];
        if (!empty($aiParsed['area_name'])) {
            $suggestions[] = "Phòng trọ khác tại {$aiParsed['area_name']}";
        }
        $suggestions[] = "Phòng có điều hòa & nóng lạnh";
        $suggestions[] = "Phòng dưới 2 triệu";
        $suggestions[] = "Phòng studio có gác xép";
        $suggestions = array_slice(array_unique($suggestions), 0, 4);

        // 6. Tạo câu phản hồi tự nhiên & súc tích, chỉ nói về 2 phòng (hoặc 1 nếu có 1)
        $message = '';
        if ($totalMatches >= 2) {
            if ($isBudgetFriendly) {
                $message = "Mình đã tìm thấy **2 phòng trọ giá tốt nhất** phù hợp với yêu cầu của bạn:";
            } else {
                $message = "Mình đã tìm thấy **2 phòng trọ phù hợp nhất** với yêu cầu của bạn:";
            }
            if (!empty($aiParsed['explanation'])) {
                $message .= "\n\n💡 *" . $aiParsed['explanation'] . "*";
            }
        } elseif ($totalMatches === 1) {
            $message = "Mình đã tìm thấy **1 phòng trọ phù hợp nhất** với yêu cầu của bạn:";
            if (!empty($aiParsed['explanation'])) {
                $message .= "\n\n💡 *" . $aiParsed['explanation'] . "*";
            }
        } else {
            $message = "Hiện chưa có phòng trọ nào khớp hoàn toàn với yêu cầu này.";
            if (!empty($aiParsed['explanation'])) {
                $message .= " (" . $aiParsed['explanation'] . ")";
            }
            $countGoiY = min(2, $top2Posts->count());
            if ($isBudgetFriendly) {
                $message .= "\n\nTuy nhiên, mình xin gợi ý **{$countGoiY} phòng trọ có mức giá rẻ nhất hiện tại** bên dưới để bạn tham khảo nhé! ✨";
            } elseif ($targetPrice !== null) {
                $message .= "\n\nTuy nhiên, mình xin gợi ý **{$countGoiY} phòng trọ có mức giá sát nhất** bên dưới để bạn tham khảo nhé! ✨";
            } else {
                $message .= "\n\nTuy nhiên, mình xin gợi ý **{$countGoiY} phòng trọ nổi bật mới nhất** đang sẵn sàng cho thuê bên dưới để bạn tham khảo nhé! ✨";
            }
        }

        // 7. Lưu vào bảng lịch sử chat nếu người dùng đã đăng nhập
        if ($userId) {
            // Lưu tin nhắn User
            AiChatHistory::create([
                'user_id' => $userId,
                'sender' => 'user',
                'message' => $prompt,
            ]);

            // Lưu tin nhắn AI
            AiChatHistory::create([
                'user_id' => $userId,
                'sender' => 'ai',
                'message' => $message,
                'rooms_data' => $rooms,
                'ai_parsed' => $aiParsed,
                'suggestions' => $suggestions,
            ]);
        }

        return [
            'success' => true,
            'message' => $message,
            'ai_parsed' => $aiParsed,
            'rooms' => $rooms,
            'total_matches' => $totalMatches,
            'suggestions' => $suggestions,
            'original_prompt' => $prompt,
        ];
    }

    /**
     * Lấy lịch sử trò chuyện AI trong vòng 7 ngày của tài khoản
     */
    public function getChatHistory(int $userId): array
    {
        // 1. Tự động xóa các tin nhắn cũ hơn 7 ngày
        AiChatHistory::where('user_id', $userId)
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        // 2. Lấy danh sách tin nhắn 7 ngày gần nhất
        $histories = AiChatHistory::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'asc')
            ->get();

        return $histories->map(function ($item) {
            return [
                'id' => 'db-' . $item->id,
                'sender' => $item->sender,
                'text' => $item->message,
                'time' => $item->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('H:i'),
                'created_at' => $item->created_at->toIso8601String(),
                'rooms' => $item->rooms_data ?: [],
                'ai_parsed' => $item->ai_parsed ?: null,
                'suggestions' => $item->suggestions ?: [],
            ];
        })->toArray();
    }

    /**
     * Xóa sạch lịch sử chat của tài khoản
     */
    public function clearChatHistory(int $userId): bool
    {
        AiChatHistory::where('user_id', $userId)->delete();
        return true;
    }

    /**
     * Đồng bộ lịch sử chat từ khách (Guest localStorage) vào tài khoản khi đăng nhập
     */
    public function syncGuestChatHistory(int $userId, array $guestMessages): bool
    {
        // Xóa tin nhắn quá 7 ngày trước
        AiChatHistory::where('user_id', $userId)
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        $cutoff = now()->subDays(7);

        foreach ($guestMessages as $msg) {
            if (empty($msg['text'])) continue;
            
            $sender = ($msg['sender'] ?? 'user') === 'ai' ? 'ai' : 'user';
            $createdAt = !empty($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at']) : now();
            
            if ($createdAt->lt($cutoff)) continue;

            AiChatHistory::create([
                'user_id' => $userId,
                'sender' => $sender,
                'message' => $msg['text'],
                'rooms_data' => $msg['rooms'] ?? null,
                'ai_parsed' => $msg['ai_parsed'] ?? null,
                'suggestions' => $msg['suggestions'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return true;
    }

    /**
     * Gợi ý 3 phòng trọ tương tự và sát nhất từ Trợ lý AI khi người dùng Không ưng sau khi xem phòng
     */
    public function getAiAlternativeRecommendationsForAppointment(\App\Models\Appointment $appointment, ?string $reason = null): array
    {
        $appointment->loadMissing(['room.boardingHouse.landlord', 'room.floor', 'room.services']);
        $currentRoom = $appointment->room;
        if (!$currentRoom) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy thông tin phòng đã xem.',
                'rooms' => [],
            ];
        }

        $currentPost = RoomPost::where('room_id', $currentRoom->id)->first();
        $currentPrice = (float) ($currentRoom->price ?? 0);
        $currentArea = (float) ($currentRoom->area ?? 0);
        $currentHouse = $currentRoom->boardingHouse;
        $currentDistrict = $currentHouse?->district ?? '';
        $currentWard = $currentHouse?->address_detail ?? '';
        $reasonLower = mb_strtolower(trim($reason ?: ($appointment->feedback_reason ?? '')));

        // 1. Lọc tất cả các bài đăng đã duyệt, phòng còn chỗ, loại trừ phòng hiện tại
        $candidatePosts = RoomPost::with(['room.boardingHouse.landlord', 'room.floor', 'room.services', 'landlord'])
            ->where('status', 'approved')
            ->where('room_id', '!=', $currentRoom->id)
            ->whereHas('room', function ($rq) {
                $rq->whereNotIn('status', ['maintenance', 'suspended', 'under_construction']);
            })
            ->get()
            ->filter(function ($post) {
                $room = $post->room;
                if (!$room) return false;
                $cap = max(1, (int) ($room->capacity ?? 1));
                $cur = (int) ($room->current_people ?? 0);
                return $cur < $cap;
            });

        // 2. Chấm điểm độ tương đồng & tối ưu theo lý do không ưng (Similarity & Reason Scoring)
        $isPriceConcern = (stripos($reasonLower, 'giá') !== false || stripos($reasonLower, 'đắt') !== false || stripos($reasonLower, 'cao') !== false || stripos($reasonLower, 'tiền') !== false);
        $isAreaConcern = (stripos($reasonLower, 'diện tích') !== false || stripos($reasonLower, 'nhỏ') !== false || stripos($reasonLower, 'chật') !== false || stripos($reasonLower, 'hẹp') !== false);
        $isLocationConcern = (stripos($reasonLower, 'xa') !== false || stripos($reasonLower, 'vị trí') !== false || stripos($reasonLower, 'đường') !== false || stripos($reasonLower, 'khu vực') !== false);
        $isAmenityConcern = (stripos($reasonLower, 'tiện nghi') !== false || stripos($reasonLower, 'ảnh') !== false || stripos($reasonLower, 'cũ') !== false || stripos($reasonLower, 'thiếu') !== false);

        $scoredCandidates = $candidatePosts->map(function ($post) use (
            $currentPrice,
            $currentArea,
            $currentDistrict,
            $currentWard,
            $isPriceConcern,
            $isAreaConcern,
            $isLocationConcern,
            $isAmenityConcern
        ) {
            $room = $post->room;
            $house = $room?->boardingHouse;
            $score = 0.0;

            $candPrice = (float) ($room?->price ?? 0);
            $candArea = (float) ($room?->area ?? 0);
            $candDistrict = $house?->district ?? '';
            $candWard = $house?->address_detail ?? '';

            // A. Điểm vị trí khu vực
            if (!empty($currentDistrict) && stripos($candDistrict, $currentDistrict) !== false) {
                $score += 35;
                if (!empty($currentWard) && stripos($candWard, $currentWard) !== false) {
                    $score += 15;
                }
            }

            // C. Điểm mức giá (Price)
            if ($currentPrice > 0 && $candPrice > 0) {
                if ($isPriceConcern) {
                    // Nếu chê đắt: Ưu tiên phòng có giá thấp hơn
                    if ($candPrice < $currentPrice) {
                        $diffRatio = ($currentPrice - $candPrice) / $currentPrice;
                        $score += 50 + (1 - min(1, abs($diffRatio - 0.2))) * 20;
                    } else {
                        $score -= 30; // Trừ điểm phòng đắt hơn
                    }
                } else {
                    // Độ sát giá (|candPrice - currentPrice|)
                    $priceDiffRatio = abs($candPrice - $currentPrice) / $currentPrice;
                    $score += max(0, (1 - $priceDiffRatio)) * 35;
                }
            }

            // D. Điểm diện tích (Area)
            if ($currentArea > 0 && $candArea > 0) {
                if ($isAreaConcern) {
                    // Nếu chê nhỏ: Ưu tiên phòng rộng hơn
                    if ($candArea > $currentArea) {
                        $score += 45;
                    }
                } else {
                    $areaDiffRatio = abs($candArea - $currentArea) / $currentArea;
                    $score += max(0, (1 - $areaDiffRatio)) * 20;
                }
            }

            // E. Điểm tiện nghi (Amenities)
            $serviceCount = $room?->services ? $room->services->count() : 0;
            if ($isAmenityConcern) {
                $score += $serviceCount * 8;
            } else {
                $score += $serviceCount * 3;
            }

            // F. Ưu tiên phòng còn trống hoàn toàn
            if ((int) ($room?->current_people ?? 0) === 0) {
                $score += 10;
            }

            return [
                'post' => $post,
                'score' => $score,
            ];
        });

        // Sắp xếp theo điểm cao nhất và lấy top 3
        $top3 = $scoredCandidates->sortByDesc('score')->take(3)->pluck('post');

        // 3. Format dữ liệu phòng trả về
        $formattedRooms = $top3->map(function ($post) {
            $room = $post->room;
            $house = $room?->boardingHouse;
            $currentPeople = (int) ($room?->current_people ?? 0);
            $capacity = max(1, (int) ($room?->capacity ?? 1));
            $hasResidents = ($currentPeople > 0);

            $status = $room?->status ?? 'available';
            $statusLabel = $hasResidents ? "Đã có {$currentPeople} người ở" : 'Còn phòng';

            $services = $room?->services ? $room->services->pluck('name')->take(3)->toArray() : [];

            return [
                'id' => $post->id,
                'room_id' => $room?->id,
                'title' => $post->title,
                'room_number' => $room?->room_number ?? 'Phòng',
                'slug' => $post->slug_with_hash,
                'url' => route('chitiettro', $post->slug_with_hash, false),
                'price' => (float) ($room?->price ?? 0),
                'price_formatted' => number_format($room?->price ?? 0, 0, ',', '.') . ' đ/tháng',
                'area' => $room?->area ?? null,
                'capacity' => $capacity,
                'current_people' => $currentPeople,
                'has_residents' => $hasResidents,
                'status' => $status,
                'status_label' => $statusLabel,
                'floor' => $room?->floor?->name ?? null,
                'address' => $house?->address_detail ?: ($house?->district ?: 'Ninh Bình'),
                'district' => $house?->district ?? '',
                'image' => $this->formatImageUrl($post->image),
                'landlord_name' => $post->landlord?->name ?? 'Chủ trọ',
                'services' => $services,
                'badge' => 'Gợi ý phù hợp nhất',
            ];
        })->values()->toArray();

        // 4. Tạo câu thông điệp AI giải thích thông minh
        $roomName = $currentRoom->room_number ? "Phòng " . $currentRoom->room_number : ($currentPost->title ?? "phòng vừa xem");
        $houseName = $currentHouse?->name ? " tại " . $currentHouse->name : "";
        $priceText = $currentPrice > 0 ? number_format($currentPrice, 0, ',', '.') . " đ" : "";
        
        $reasonText = !empty($reason) ? trim($reason) : ($appointment->feedback_reason ?: "chưa ưng ý");
        
        $aiMessage = "Dựa trên lý do bạn chưa ưng ý (\"{$reasonText}\") với {$roomName}{$houseName}, Trợ lý AI đã chọn lọc 3 phòng trọ có vị trí, mức giá và tiện ích tương tự sát nhất để bạn tham khảo ngay:";

        return [
            'success' => true,
            'viewed_room' => [
                'room_number' => $currentRoom->room_number ?? 'Phòng đã xem',
                'house_name' => $currentHouse?->name ?? 'Nhà trọ',
                'address' => $currentHouse?->address_detail ?: ($currentHouse?->district ?: 'Ninh Bình'),
                'price_formatted' => $priceText ? $priceText . '/tháng' : null,
                'reason' => $reasonText,
            ],
            'ai_message' => $aiMessage,
            'rooms' => $formattedRooms,
        ];
    }
}