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

        //nếu phòng đã có người ở -> tự đồng tạo yêu cầu ở ghép
        if($post->room && $post->room->current_people > 0){
            \App\Models\RoommateRequest::create([
                'room_id' => $post->room_id,
                'tenant_id' => $userId,
                'type' => 'stranger',
                'status' => 'pending',
                'note' => $note ?: 'Khách đặt lịch xem phòng và muốn ở ghép',
            ]);
        }
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
     * Chuẩn hóa đường dẫn ảnh phòng trọ
     */
    public function formatImageUrl($image): string
    {
        if (empty($image)) return '/anh/banner_tro.png';
        $firstImg = is_array($image) ? ($image[0] ?? '') : $image;
        if (empty($firstImg) || !is_string($firstImg)) return '/anh/banner_tro.png';
        $trimmed = trim($firstImg);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '/storage/')) return $trimmed;
        if (str_starts_with($trimmed, 'storage/')) return '/' . $trimmed;
        if (str_starts_with($trimmed, '/')) return $trimmed;
        return '/storage/' . $trimmed;
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

        // 2. Xây dựng truy vấn lọc chính xác theo các tiêu chí AI bóc tách
        $query = RoomPost::with(['room.boardingHouse', 'room.floor', 'room.services', 'landlord'])
            ->where('status', 'approved');

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

        // 3. Thực thi truy vấn kết quả khớp chuẩn
        $matchingPosts = $query->get();
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

                // Ưu tiên 2: Còn phòng (available)
                $aAvailable = ($a->room && $a->room->status === 'available') ? 1 : 0;
                $bAvailable = ($b->room && $b->room->status === 'available') ? 1 : 0;
                if ($aAvailable !== $bAvailable) {
                    return $bAvailable <=> $aAvailable;
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
            // Không tìm thấy phòng khớp hoàn toàn -> Lấy tất cả bài đăng đã duyệt để tìm phòng tối ưu nhất
            $allApprovedPosts = RoomPost::with(['room.boardingHouse', 'room.floor', 'room.services', 'landlord'])
                ->where('status', 'approved')
                ->get();

            if ($isBudgetFriendly) {
                // SẮP XẾP THEO GIÁ RẺ NHẤT TOÀN HỆ THỐNG
                $sortedPosts = $allApprovedPosts->sort(function ($a, $b) {
                    $priceA = (float) ($a->room?->price ?? PHP_INT_MAX);
                    $priceB = (float) ($b->room?->price ?? PHP_INT_MAX);
                    if ($priceA !== $priceB) {
                        return $priceA <=> $priceB;
                    }
                    $aAvailable = ($a->room && $a->room->status === 'available') ? 1 : 0;
                    $bAvailable = ($b->room && $b->room->status === 'available') ? 1 : 0;
                    if ($aAvailable !== $bAvailable) {
                        return $bAvailable <=> $aAvailable;
                    }
                    $aTime = $a->updated_at ? $a->updated_at->timestamp : 0;
                    $bTime = $b->updated_at ? $b->updated_at->timestamp : 0;
                    return $bTime <=> $aTime;
                });
                $top2Posts = $sortedPosts->take(2);
            } elseif ($targetPrice !== null) {
                // SẮP XẾP THEO KHOẢNG CÁCH GIÁ SÁT NHẤT (|room.price - targetPrice| nhỏ nhất)
                $sortedByProximity = $allApprovedPosts->sort(function ($a, $b) use ($targetPrice) {
                    $priceA = (float) ($a->room?->price ?? PHP_INT_MAX);
                    $priceB = (float) ($b->room?->price ?? PHP_INT_MAX);
                    $diffA = abs($priceA - $targetPrice);
                    $diffB = abs($priceB - $targetPrice);

                    if ($diffA !== $diffB) {
                        return $diffA <=> $diffB;
                    }

                    $aAvailable = ($a->room && $a->room->status === 'available') ? 1 : 0;
                    $bAvailable = ($b->room && $b->room->status === 'available') ? 1 : 0;
                    if ($aAvailable !== $bAvailable) {
                        return $bAvailable <=> $aAvailable;
                    }

                    $aTime = $a->updated_at ? $a->updated_at->timestamp : 0;
                    $bTime = $b->updated_at ? $b->updated_at->timestamp : 0;
                    return $bTime <=> $aTime;
                });
                $top2Posts = $sortedByProximity->take(2);
            } else {
                // Nếu không có điều kiện giá: Ưu tiên còn phòng -> Giá rẻ hơn -> Mới nhất
                $sortedPosts = $allApprovedPosts->sort(function ($a, $b) {
                    $aAvailable = ($a->room && $a->room->status === 'available') ? 1 : 0;
                    $bAvailable = ($b->room && $b->room->status === 'available') ? 1 : 0;
                    if ($aAvailable !== $bAvailable) {
                        return $bAvailable <=> $aAvailable;
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
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug_with_hash,
                'url' => route('chitiettro', $post->slug_with_hash, false),
                'price' => $room?->price ?? 0,
                'price_formatted' => number_format($room?->price ?? 0, 0, ',', '.') . ' đ/tháng',
                'area' => $room?->area ?? null,
                'address' => $house?->address_detail ?: ($house?->district ?: 'Ninh Bình'),
                'image' => $this->formatImageUrl($post->image),
                'status' => $room?->status ?? 'available',
                'status_label' => match($room?->status ?? 'available') {
                    'available' => 'Còn phòng',
                    'rented' => 'Đã thuê',
                    'maintenance' => 'Bảo trì',
                    'deposited' => 'Đã cọc',
                    'expiring_soon' => 'Sắp hết hạn',
                    'pending_renewal' => 'Chờ gia hạn',
                    'suspended' => 'Tạm ngưng',
                    'under_construction' => 'Đang xây',
                    default => 'Còn phòng'
                },
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
}