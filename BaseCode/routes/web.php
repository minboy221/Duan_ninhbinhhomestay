<?php

use App\Http\Controllers\AdminVerificationController;
// Phần hồ sơ
use App\Http\Controllers\ProfileController;
// Phần admin
use App\Http\Controllers\AdminController;
// Phần danh mục
use App\Http\Controllers\CategoryController;
// Phần chủ trọ
use App\Http\Controllers\LandlordController;
// Phần bài đăng tin cho phòng trọ
use App\Http\Controllers\RoomListingController;
// Phần danh mục dịch vụ cho phòng trọ
use App\Services\CategoryService;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Application;
// Phần xác minh thông tin chủ trọ
use App\Http\Controllers\Api\VerificationController;

//Phần đặt lịch xem phòng
use App\Http\Controllers\Client\PublicListingController;

use App\Http\Controllers\PostController;
use App\Http\Controllers\AdminPostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route phần clien
Route::get('/', function (CategoryService $categoryService) {
    $categoryData = $categoryService->getActiveData();
    return Inertia::render('Client/Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('signup'),
        'canVerfyEmail' => Route::has('canverfyemail'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'categories' => $categoryData['types'],
        'areas' => $categoryData['areas'],
        'amenities' => $categoryData['amenities'],
    ]);
})->name('home');

// Route cho Trang Giới thiệu
Route::get('/about', function () {
    return Inertia::render('Client/About'); // Trỏ đến file Pages/Client/About.vue
})->name('about');

// Route cho Trang Tìm trọ
Route::get('/timtro', [PublicListingController::class, 'index'])->name('timtro');

// Route cho Trang Tin tức
Route::get('/tintuc', [PostController::class, 'index'])->name('tintuc');
Route::get('/tintuc/suggest', [PostController::class, 'suggest'])->name('tintuc.suggest');

// Route cho Trang Liên hệ
Route::get('/lienhe', function () {
    return Inertia::render('Client/lienhe'); // Trỏ đến file Pages/Client/About.vue
})->name('lienhe');

// Route cho Trang chi tiết trọ
Route::get('/chitiettro/{id?}', [PublicListingController::class, 'show'])->name('chitiettro');

// Route cho Trang chi tiết tin tức (lấy động theo slug)
Route::get('/tintuc/{slug}', [PostController::class, 'show'])->name('chitiettintuc');
//Route cho phần đếm ngược giờ đặt lịch
Route::get('/api/user/today-appointments', [PublicListingController::class, 'getTodayAppointment'])->middleware('auth');

//Route xử lý phản hồi cuộc họp xem phòng của clien
Route::post('/api/appointments/{id}/feedback',[PublicListingController::class,'submitFeedback'])->middleware('auth');

// Route cho Trang điều khoản và chính sách
Route::get('/chitietdieukhoan', function () {
    return Inertia::render('Client/dieukhoan'); // Trỏ đến file Pages/Client/About.vue
})->name('chitietdieukhoan');


//PHẦN NÀY ĐỂ LÀM LOGIC ĐĂNG NHẬP SAU
Route::middleware('auth')->group(function () {
    Route::get('/tranguser', [ProfileController::class, 'index'])->name('tranguser');
    Route::post('/tranguser', [ProfileController::class, 'updateProfile'])->name('tranguser.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::get('/quanlynoio', [ProfileController::class, 'quanlynoio'])->name('quanlynoio');
    Route::get('/lichsuthanhtoan', [ProfileController::class, 'lichsuthanhtoan'])->name('lichsuthanhtoan');
    Route::post('/invoices/{id}/notify-payment', [ProfileController::class, 'notifyPayment'])->name('invoices.notify-payment');
    Route::get('/caidatuser', [ProfileController::class, 'caidatuser'])->name('caidatuser');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Route lịch hẹn của khách thuê
    Route::get('/lichhen', [ProfileController::class, 'appointments'])->name('profile.appointments');
    Route::post('/chitiettro/{id}/book', [PublicListingController::class, 'book'])->name('rooms.book');
    //route API layas các khung giờ đã trùng
    Route::get('/chitiettro/{id}/booked_slots', [PublicListingController::class, 'getBookedSlots'])->name('rooms.booked-slots');

    // Route thả tim (yêu thích) phòng trọ
    Route::post('/rooms/{room}/favorite', [ProfileController::class, 'toggleFavorite'])->name('rooms.favorite');

    // Route Đánh giá sau khi xem phòng
    Route::post('/appointments/{appointment}/review', [ProfileController::class, 'submitReview'])->name('appointments.review');
    Route::post('/appointments/{appointment}/interest', [ProfileController::class, 'submitInterest'])->name('appointments.interest');

    // Route chung để xem file private (CCCD, Hợp đồng...)
    Route::get('/files/private/{type}/{filename}', [AdminVerificationController::class, 'showPrivateFile'])
        ->name('files.private');
});

// ROUTER cho admin
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    // Phần Duyệt cơ sở mới của Admin
    Route::get('/boarding-houses', [\App\Http\Controllers\AdminBoardingHouseController::class, 'index'])->name('admin.boarding-houses.index');
    Route::get('/boarding-houses/{id}', [\App\Http\Controllers\AdminBoardingHouseController::class, 'show'])->name('admin.boarding-houses.show');
    Route::post('/boarding-houses/{id}/approve', [\App\Http\Controllers\AdminBoardingHouseController::class, 'approve'])->name('admin.boarding-houses.approve');
    Route::post('/boarding-houses/{id}/reject', [\App\Http\Controllers\AdminBoardingHouseController::class, 'reject'])->name('admin.boarding-houses.reject');

    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.users.toggle-status');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::get('/landlords', [AdminController::class, 'landlords'])->name('admin.landlords');
    // Phần Duyệt tin đăng của Admin với chủ trọ
    Route::get('/approval', [AdminController::class, 'approval'])->name('admin.listings.index');
    //Phần xem chi tiết tin đăng của admin khi user đăng lên
    Route::get('/approval/{id}', [AdminController::class, 'showApproval'])->name('admin.listings.show');
    //phần sử lý duyệt tin của admin
    Route::post('/listings/{id}/approve', [AdminController::class, 'approveListing'])->name('admin.listings.approve');
    Route::post('/listings/{id}/reject', [AdminController::class, 'rejectListing'])->name('admin.listings.reject');

    // CRUD routes cho Danh mục (Loại phòng)
    Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories');
    Route::post('/categories/types', [CategoryController::class, 'storeCategory'])->name('admin.categories.types.store');
    Route::put('/categories/types/{id}', [CategoryController::class, 'updateCategory'])->name('admin.categories.types.update');
    Route::delete('/categories/types/{id}', [CategoryController::class, 'deleteCategory'])->name('admin.categories.types.delete');
    Route::patch('/categories/types/{id}/toggle', [CategoryController::class, 'toggleCategory'])->name('admin.categories.types.toggle');

    // CRUD routes cho Khu vực
    Route::post('/categories/areas', [CategoryController::class, 'storeArea'])->name('admin.categories.areas.store');
    Route::put('/categories/areas/{id}', [CategoryController::class, 'updateArea'])->name('admin.categories.areas.update');
    Route::delete('/categories/areas/{id}', [CategoryController::class, 'deleteArea'])->name('admin.categories.areas.delete');
    Route::patch('/categories/areas/{id}/toggle', [CategoryController::class, 'toggleArea'])->name('admin.categories.areas.toggle');

    // CRUD routes cho Tiện ích
    Route::post('/categories/amenities', [CategoryController::class, 'storeAmenity'])->name('admin.categories.amenities.store');
    Route::put('/categories/amenities/{id}', [CategoryController::class, 'updateAmenity'])->name('admin.categories.amenities.update');
    Route::delete('/categories/amenities/{id}', [CategoryController::class, 'deleteAmenity'])->name('admin.categories.amenities.delete');
    Route::patch('/categories/amenities/{id}/toggle', [CategoryController::class, 'toggleAmenity'])->name('admin.categories.amenities.toggle');

    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/reviews', [AdminController::class, 'reviews'])->name('admin.reviews');
    Route::get('/revenue', [AdminController::class, 'revenue'])->name('admin.revenue');
    Route::get('/roles', [AdminController::class, 'roles'])->name('admin.roles');
    Route::get('/auditlog', [AdminController::class, 'auditlog'])->name('admin.auditlog');
    Route::get('/website', [AdminController::class, 'website'])->name('admin.website');
    Route::post('/website', [AdminController::class, 'updateWebsite'])->name('admin.website.update');
    Route::get('/ads', [AdminController::class, 'ads'])->name('admin.ads');
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    //Phần route để xác minh thông tin chủ trọ
    Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('admin.verifications.index');
    //xem chi tiết 1 hồ sơ
    Route::get('/verifications/{userId}', [AdminVerificationController::class, 'show'])->name('admin.verifications.show');
    //xử lý duyệt hồ sơ/từ chối
    Route::post('/verifications/{userId}/status', [AdminVerificationController::class, 'updateStatus'])->name('admin.verifications.update-status');
    //Route Đặc biệt: để admin xem được ảnh lưu trong thư mục private
    Route::get('/files/private/{type}/{filename}', [AdminVerificationController::class, 'showPrivateFile'])
        ->name('admin.files.private');
    // CRUD routes cho Tin tức (Bài viết)
    Route::get('/posts', [AdminPostController::class, 'index'])->name('admin.posts.index');
    Route::get('/posts/create', [AdminPostController::class, 'create'])->name('admin.posts.create');
    Route::post('/posts', [AdminPostController::class, 'store'])->name('admin.posts.store');
    Route::get('/posts/{id}/edit', [AdminPostController::class, 'edit'])->name('admin.posts.edit');
    Route::post('/posts/{id}', [AdminPostController::class, 'update'])->name('admin.posts.update');
    Route::delete('/posts/{id}', [AdminPostController::class, 'destroy'])->name('admin.posts.destroy');

    // Các route trên đã định nghĩa đầy đủ
});

// ROUTER cho landlord (chủ trọ)
Route::middleware(['auth', 'landlord'])->prefix('landlord')->group(function () {
    Route::get('/dashboard', [LandlordController::class, 'dashboard'])->name('landlord.dashboard');

    // Phần quản lý cơ sở của chủ trọ
    Route::get('/boarding-houses', [\App\Http\Controllers\Landlord\BoardingHouseController::class, 'index'])->name('landlord.boarding-houses.index');
    Route::get('/boarding-houses/history', [\App\Http\Controllers\Landlord\BoardingHouseController::class, 'history'])->name('landlord.boarding-houses.history');
    Route::get('/boarding-houses/create', [\App\Http\Controllers\Landlord\BoardingHouseController::class, 'create'])->name('landlord.boarding-houses.create');
    Route::post('/boarding-houses', [\App\Http\Controllers\Landlord\BoardingHouseController::class, 'store'])->name('landlord.boarding-houses.store');
    Route::get('/boarding-houses/{id}', [\App\Http\Controllers\Landlord\BoardingHouseController::class, 'show'])->name('landlord.boarding-houses.show');
    Route::post('/select-boarding-house', [\App\Http\Controllers\Landlord\BoardingHouseController::class, 'selectBoardingHouse'])->name('landlord.select-boarding-house');
    Route::get('/profile', [LandlordController::class, 'profile'])->name('landlord.profile');
    Route::post('/profile', [LandlordController::class, 'updateProfile'])->name('landlord.profile.update');
    Route::get('/rooms', [LandlordController::class, 'rooms'])->name('landlord.rooms');

    // CRUD routes cho Tầng
    Route::post('/floors', [LandlordController::class, 'storeFloor'])->name('landlord.floors.store');
    Route::put('/floors/{id}', [LandlordController::class, 'updateFloor'])->name('landlord.floors.update');
    Route::delete('/floors/{id}', [LandlordController::class, 'deleteFloor'])->name('landlord.floors.delete');

    // CRUD routes cho Phòng trọ
    Route::post('/rooms', [LandlordController::class, 'storeRoom'])->name('landlord.rooms.store');
    Route::post('/rooms/{id}', [LandlordController::class, 'updateRoom'])->name('landlord.rooms.update');
    Route::patch('/rooms/{id}/status', [LandlordController::class, 'changeRoomStatus'])->name('landlord.rooms.status');
    Route::patch('/rooms/{id}/add-person', [LandlordController::class, 'addPerson'])->name('landlord.rooms.add_person');
    Route::patch('/rooms/{id}/remove-person', [LandlordController::class, 'removePerson'])->name('landlord.rooms.remove_person');
    Route::delete('/rooms/{id}', [LandlordController::class, 'deleteRoom'])->name('landlord.rooms.delete');

    //CRUD tin đăng phòng trọ
    Route::get('/listings', [RoomListingController::class, 'index'])->name('landlord.listings.index');
    Route::get('/listings/create', [RoomListingController::class, 'create'])->name('landlord.listings.create');
    // Route lấy chi tiết phòng để đăng tin
    Route::get('/rooms/{id}/details-for-listing', [RoomListingController::class, 'getRoomDetails'])->name('landlord.rooms.details');
    Route::post('/listings', [RoomListingController::class, 'store'])->name('landlord.listings.store');
    Route::get('/listings/{id}/edit', [RoomListingController::class, 'edit'])->name('landlord.listings.edit');
    Route::put('/listings/{id}', [RoomListingController::class, 'update'])->name('landlord.listings.update');
    Route::delete('/listings/{id}', [RoomListingController::class, 'destroy'])->name('landlord.listings.destroy');
    Route::post('/listings/{id}/close', [RoomListingController::class, 'close'])->name('landlord.listings.close');
    // Lấy dịch vụ tiện ích của các phòng
    Route::get('/rooms/{id}/services', [RoomListingController::class, 'getRoomServices']);

    //Phần hiển thị đặt lịch của chủ trọ
    Route::get('/appointments', [LandlordController::class, 'appointments'])->name('landlord.appointments');
    Route::post('/appointments/{id}/approve', [LandlordController::class, 'approveAppointment'])->name('landlord.appointments.approve');
    Route::post('/appointments/{id}/reject', [LandlordController::class, 'rejectAppointment'])->name('landlord.appointments.reject');
    Route::get('/appointments/availabilities', [LandlordController::class, 'editAvailabilities'])->name('landlord.availabilities.edit');
    Route::post('/appointments/availabilities', [LandlordController::class, 'storeAvailabilities'])->name('landlord.availabilities.store');

    Route::get('/tenants', [LandlordController::class, 'tenants'])->name('landlord.tenants');
    Route::get('/contracts', [LandlordController::class, 'contracts'])->name('landlord.contracts');
    
    // Đăng ký hợp đồng (Phase 3, 4, 5)
    Route::get('/contracts/create-draft', [\App\Http\Controllers\Landlord\ContractController::class, 'createDraft'])->name('landlord.contracts.create_draft');
    Route::post('/contracts/store-draft', [\App\Http\Controllers\Landlord\ContractController::class, 'storeDraftAndExport'])->name('landlord.contracts.store_draft');
    Route::post('/contracts/{contract}/upload-signed', [\App\Http\Controllers\Landlord\ContractController::class, 'uploadSignedContract'])->name('landlord.contracts.upload_signed');

    Route::get('/invoices', [LandlordController::class, 'invoices'])->name('landlord.invoices');
    Route::post('/invoices', [LandlordController::class, 'storeInvoice'])->name('landlord.invoices.store');
    Route::put('/invoices/{id}', [LandlordController::class, 'updateInvoice'])->name('landlord.invoices.update');
    Route::patch('/invoices/{id}/status', [LandlordController::class, 'updateInvoiceStatus'])->name('landlord.invoices.status');
    Route::delete('/invoices/{id}', [LandlordController::class, 'deleteInvoice'])->name('landlord.invoices.delete');
    Route::get('/finance', [LandlordController::class, 'finance'])->name('landlord.finance');
    Route::get('/services', [LandlordController::class, 'services'])->name('landlord.services');
    Route::post('/services', [LandlordController::class, 'storeService'])->name('landlord.services.store');
    Route::put('/services/{id}', [LandlordController::class, 'updateService'])->name('landlord.services.update');
    Route::delete('/services/{id}', [LandlordController::class, 'deleteService'])->name('landlord.services.delete');
    Route::patch('/services/{id}/status', [LandlordController::class, 'changeServiceStatus'])->name('landlord.services.status');
    Route::get('/pricing-sheets', [LandlordController::class, 'pricingSheets'])->name('landlord.pricing-sheets');
    Route::get('/pricing-sheets/create', [LandlordController::class, 'pricingSheetsCreate'])->name('landlord.pricing-sheets.create');
});
// Route cho phần xác minh đăng ký chủ trọ và thông báo
Route::middleware(['auth'])->group(function () {
    //route hiển thị giao diện 
    Route::get('/landlord/verify', [VerificationController::class, 'create'])
        ->name('landlord.verify.create');
    Route::post('landlord/verify', [VerificationController::class, 'verify'])
        ->name('landlord.verify.store');

    // Route đánh dấu thông báo đã đọc
    Route::post('/notifications/{id}/read', function ($id) {
        $notification = auth()->user()->notifications()->findOrFail($id);
        //Phần đánh dấu đã đọc
        $notification->markAsRead();
        //Lấy dữ liệu ra mảng json
        $data = $notification->data;
        //Nếu thông báo này có chứa thông tin về bài đăng tin của trọ
        if (isset($data['post_id'])) {
            //phần tin từ chối -> đẩy chủ trọ về trang sửa tin đăng
            if (($data['type'] ?? '') === 'listing_rejected') {
                return redirect()->route('landlord.listings.edit', $data['post_id'])
                    ->with('info', 'Vui lòng đọc kỹ lý do từ chối và cập nhật lại bài đăng');
            }
            //phần tin duyệt thành công-> đẩy về trang danh sách tin đăng để xem
            if (($data['type'] ?? '') === 'listing_approved') {
                return redirect()->route('landlord.listings.index')
                    ->with('success', 'Tin đăng của bạn đã được xuất bản công khai');
            }
        }
        return back();
    })->name('notifications.read');
    //phần route nhận tín hiệu heartbeat ping từ trạng thái online
    Route::post('user/ping',function(){
        return response()->json(['status' => 'success']);
    })->name('user.ping');
});
require __DIR__ . '/auth.php';

