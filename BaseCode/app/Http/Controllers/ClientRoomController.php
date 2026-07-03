<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Area;
use App\Models\Category;
use App\Models\Amenity;
use App\Models\Appointment;
use App\Notifications\NewAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientRoomController extends Controller
{
    /**
     * Display rooms list for Client Room search page.
     */
    public function index(Request $request)
    {
        $query = Room::with(['property.landlord.boardingHouse']);

        // Only show available rooms
        $query->where('status', 'available');

        // Filter by Area
        if ($request->filled('area_id')) {
            $area = Area::find($request->input('area_id'));
            if ($area) {
                $query->where(function ($q) use ($area) {
                    $q->where('address', 'like', "%{$area->name}%")
                      ->orWhereHas('property', function ($qp) use ($area) {
                          $qp->where('address', 'like', "%{$area->name}%");
                      });
                });
            }
        }

        // Filter by Price range
        if ($request->filled('price')) {
            $priceRange = $request->input('price');
            if ($priceRange === 'duoi-1-trieu') {
                $query->where('price', '<', 1000000);
            } elseif ($priceRange === '1-2-trieu') {
                $query->whereBetween('price', [1000000, 2000000]);
            } elseif ($priceRange === '2-3-trieu') {
                $query->whereBetween('price', [2000000, 3000000]);
            } elseif ($priceRange === 'tren-3-trieu') {
                $query->where('price', '>', 3000000);
            }
        }

        // Filter by Size (Diện tích)
        if ($request->filled('dientich')) {
            $sizeRange = $request->input('dientich');
            if ($sizeRange === 'duoi-20') {
                $query->where('area', '<', 20);
            } elseif ($sizeRange === '20-30') {
                $query->whereBetween('area', [20, 30]);
            } elseif ($sizeRange === '30-50') {
                $query->whereBetween('area', [30, 50]);
            } elseif ($sizeRange === 'tren-50') {
                $query->where('area', '>', 50);
            }
        }

        // Filter by categories (Loại phòng)
        if ($request->filled('categories') && is_array($request->input('categories'))) {
            $catIds = $request->input('categories');
            $catNames = Category::whereIn('id', $catIds)->pluck('name')->toArray();
            
            $query->whereHas('property', function ($qp) use ($catNames) {
                $qp->where(function ($sub) use ($catNames) {
                    foreach ($catNames as $name) {
                        // Map database enum type with category names
                        if (stripos($name, 'homestay') !== false) {
                            $sub->orWhere('type', 'homestay');
                        } elseif (stripos($name, 'nhà trọ') !== false || stripos($name, 'phòng trọ') !== false || stripos($name, 'motel') !== false) {
                            $sub->orWhere('type', 'motel_room');
                        } elseif (stripos($name, 'căn hộ') !== false || stripos($name, 'chung cư') !== false || stripos($name, 'apartment') !== false) {
                            $sub->orWhere('type', 'apartment');
                        }
                    }
                });
            });
        }

        // Filter by Amenities (Tiện ích)
        if ($request->filled('amenities') && is_array($request->input('amenities'))) {
            $amenityIds = $request->input('amenities');
            $amenityNames = Amenity::whereIn('id', $amenityIds)->pluck('name')->toArray();

            foreach ($amenityNames as $name) {
                $query->where('amenities', 'like', "%{$name}%");
            }
        }

        // Paginate results
        $rooms = $query->orderBy('created_at', 'desc')->paginate(6)->withQueryString();

        // Get Categories, Areas, Amenities for Filter Sidebar
        $categories = Category::where('is_active', true)->get();
        $areas = Area::where('is_active', true)->get();
        $amenities = Amenity::where('is_active', true)->get();

        return Inertia::render('Client/timtro', [
            'rooms' => $rooms,
            'categories' => $categories,
            'areas' => $areas,
            'amenities' => $amenities,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Show Room Details page.
     */
    public function show($id = null)
    {
        // If no ID is specified, show the first available room
        if ($id === null) {
            $room = Room::with(['property.landlord.boardingHouse', 'floor'])->where('status', 'available')->first();
            if (!$room) {
                return redirect()->route('timtro')->with('error', 'Không tìm thấy phòng trọ nào.');
            }
            return redirect()->route('chitiettro', $room->id);
        }

        $room = Room::with(['property.landlord.boardingHouse', 'floor'])->findOrFail($id);

        // Fetch similar rooms in same area/property
        $similarRooms = Room::with(['property.landlord'])
            ->where('id', '!=', $room->id)
            ->where('status', 'available')
            ->where(function ($q) use ($room) {
                $q->where('property_id', $room->property_id)
                  ->orWhere('address', 'like', "%{$room->property->city}%");
            })
            ->limit(3)
            ->get();

        $isFavorited = false;
        if (Auth::check()) {
            $isFavorited = \App\Models\Favorite::where('user_id', Auth::id())
                ->where('room_id', $room->id)
                ->exists();
        }

        return Inertia::render('Client/chitiettro', [
            'room' => $room,
            'similarRooms' => $similarRooms,
            'is_favorited' => $isFavorited
        ]);
    }

    /**
     * Book appointment to view room.
     */
    public function book(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn phải đăng nhập để đặt lịch xem phòng.');
        }

        $todayStr = \Carbon\Carbon::today()->format('Y-m-d');
        $tomorrowStr = \Carbon\Carbon::tomorrow()->format('Y-m-d');

        $request->validate([
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($todayStr, $tomorrowStr) {
                    if ($value !== $todayStr && $value !== $tomorrowStr) {
                        $fail('Bạn chỉ có thể đặt lịch hẹn trong ngày hôm nay hoặc ngày mai.');
                    }
                }
            ],
            'time' => [
                'required',
                function ($attribute, $value, $fail) use ($request, $todayStr) {
                    if ($request->input('date') === $todayStr) {
                        $currentTime = \Carbon\Carbon::now()->format('H:i');
                        if ($value < $currentTime) {
                            $fail('Không thể chọn giờ hẹn trong quá khứ.');
                        }
                    }
                }
            ],
            'note' => 'nullable|string|max:500',
        ], [
            'date.required' => 'Vui lòng chọn ngày hẹn.',
            'time.required' => 'Vui lòng chọn giờ hẹn xem phòng.',
        ]);

        $room = Room::with('property')->findOrFail($id);
        $landlordId = $room->property->landlord_id;

        $appointment = Appointment::create([
            'user_id' => Auth::id(),
            'landlord_id' => $landlordId,
            'room_id' => $room->id,
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'note' => $request->input('note'),
            'status' => 'pending',
            'notified' => false,
        ]);

        // Send database notification to the landlord
        $landlord = $appointment->landlord;
        if ($landlord) {
            $landlord->notify(new NewAppointment($appointment));
        }

        return redirect()->back()->with('success', 'Gửi yêu cầu đặt lịch hẹn xem phòng thành công. Vui lòng chờ chủ trọ phê duyệt.');
    }

    /**
     * Toggle favorite room status for authenticated user.
     */
    public function toggleFavorite(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn phải đăng nhập để lưu tin đăng này.');
        }

        $userId = Auth::id();
        $favorite = \App\Models\Favorite::where('user_id', $userId)
            ->where('room_id', $id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $msg = 'Đã xóa khỏi danh sách trọ yêu thích.';
        } else {
            \App\Models\Favorite::create([
                'user_id' => $userId,
                'room_id' => $id,
            ]);
            $msg = 'Đã thêm vào danh sách trọ yêu thích.';
        }

        return redirect()->back()->with('success', $msg);
    }
}
