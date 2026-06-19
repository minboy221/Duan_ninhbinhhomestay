<?php

use App\Http\Controllers\AdminVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LandlordController;
use App\Services\CategoryService;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Application;
//Phần xác minh thông tin chủ trọ
use App\Http\Controllers\Api\VerificationController;

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
        'areas'      => $categoryData['areas'],
        'amenities'  => $categoryData['amenities'],
    ]);
})->name('home');

// Route cho Trang Giới thiệu
Route::get('/about', function () {
    return Inertia::render('Client/About'); // Trỏ đến file Pages/Client/About.vue
})->name('about');

// Route cho Trang Tìm trọ
Route::get('/timtro', function (CategoryService $categoryService) {
    $categoryData = $categoryService->getActiveData();
    return Inertia::render('Client/timtro', [
        'categories' => $categoryData['types'],
        'areas'      => $categoryData['areas'],
        'amenities'  => $categoryData['amenities'],
    ]);
})->name('timtro');

// Route cho Trang Tin tức
Route::get('/tintuc', function () {
    return Inertia::render('Client/tintuc'); // Trỏ đến file Pages/Client/About.vue
})->name('tintuc');

// Route cho Trang Liên hệ
Route::get('/lienhe', function () {
    return Inertia::render('Client/lienhe'); // Trỏ đến file Pages/Client/About.vue
})->name('lienhe');

// Route cho Trang chi tiết trọ
Route::get('/chitiettro', function () {
    return Inertia::render('Client/chitiettro'); // Trỏ đến file Pages/Client/About.vue
})->name('chitiettro');

// Route cho Trang chi tiết tin tức
Route::get('/chitiettintuc', function () {
    return Inertia::render('Client/chitiettintuc'); // Trỏ đến file Pages/Client/About.vue
})->name('chitiettintuc');

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
    Route::get('/caidatuser', [ProfileController::class, 'caidatuser'])->name('caidatuser');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Route chung để xem file private (CCCD, Hợp đồng...)
    Route::get('/files/private/{type}/{filename}', [AdminVerificationController::class, 'showPrivateFile'])
        ->name('files.private');
});

// ROUTER cho admin
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard',  [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/users',      [AdminController::class, 'users'])->name('admin.users');
    Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.users.toggle-status');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::get('/landlords',  [AdminController::class, 'landlords'])->name('admin.landlords');
    Route::get('/approval',   [AdminController::class, 'approval'])->name('admin.approval');
    Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories');

    // CRUD routes cho Danh mục (Loại phòng)
    Route::post('/categories/types',           [CategoryController::class, 'storeCategory'])->name('admin.categories.types.store');
    Route::put('/categories/types/{id}',       [CategoryController::class, 'updateCategory'])->name('admin.categories.types.update');
    Route::delete('/categories/types/{id}',    [CategoryController::class, 'deleteCategory'])->name('admin.categories.types.delete');
    Route::patch('/categories/types/{id}/toggle', [CategoryController::class, 'toggleCategory'])->name('admin.categories.types.toggle');

    // CRUD routes cho Khu vực
    Route::post('/categories/areas',           [CategoryController::class, 'storeArea'])->name('admin.categories.areas.store');
    Route::put('/categories/areas/{id}',       [CategoryController::class, 'updateArea'])->name('admin.categories.areas.update');
    Route::delete('/categories/areas/{id}',    [CategoryController::class, 'deleteArea'])->name('admin.categories.areas.delete');
    Route::patch('/categories/areas/{id}/toggle', [CategoryController::class, 'toggleArea'])->name('admin.categories.areas.toggle');

    // CRUD routes cho Tiện ích
    Route::post('/categories/amenities',           [CategoryController::class, 'storeAmenity'])->name('admin.categories.amenities.store');
    Route::put('/categories/amenities/{id}',       [CategoryController::class, 'updateAmenity'])->name('admin.categories.amenities.update');
    Route::delete('/categories/amenities/{id}',    [CategoryController::class, 'deleteAmenity'])->name('admin.categories.amenities.delete');
    Route::patch('/categories/amenities/{id}/toggle', [CategoryController::class, 'toggleAmenity'])->name('admin.categories.amenities.toggle');

    Route::get('/reports',    [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/reviews',    [AdminController::class, 'reviews'])->name('admin.reviews');
    Route::get('/revenue',    [AdminController::class, 'revenue'])->name('admin.revenue');
    Route::get('/roles',      [AdminController::class, 'roles'])->name('admin.roles');
    Route::get('/auditlog',   [AdminController::class, 'auditlog'])->name('admin.auditlog');
    Route::get('/website',    [AdminController::class, 'website'])->name('admin.website');
    Route::get('/ads',        [AdminController::class, 'ads'])->name('admin.ads');
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
    // Các route trên đã định nghĩa đầy đủ
});

// ROUTER cho landlord (chủ trọ)
Route::middleware(['auth', 'landlord'])->prefix('landlord')->group(function () {
    Route::get('/dashboard', [LandlordController::class, 'dashboard'])->name('landlord.dashboard');
    Route::get('/profile', [LandlordController::class, 'profile'])->name('landlord.profile');
    Route::get('/rooms', [LandlordController::class, 'rooms'])->name('landlord.rooms');

    // CRUD routes cho Tầng
    Route::post('/floors',              [LandlordController::class, 'storeFloor'])->name('landlord.floors.store');
    Route::put('/floors/{id}',          [LandlordController::class, 'updateFloor'])->name('landlord.floors.update');
    Route::delete('/floors/{id}',       [LandlordController::class, 'deleteFloor'])->name('landlord.floors.delete');

    // CRUD routes cho Phòng trọ
    Route::post('/rooms',                   [LandlordController::class, 'storeRoom'])->name('landlord.rooms.store');
    Route::post('/rooms/{id}',              [LandlordController::class, 'updateRoom'])->name('landlord.rooms.update');
    Route::patch('/rooms/{id}/status',      [LandlordController::class, 'changeRoomStatus'])->name('landlord.rooms.status');
    Route::delete('/rooms/{id}',            [LandlordController::class, 'deleteRoom'])->name('landlord.rooms.delete');

    Route::get('/listings', [LandlordController::class, 'listings'])->name('landlord.listings');
    Route::get('/listings/create', [LandlordController::class, 'listingCreate'])->name('landlord.listings.create');
    Route::get('/appointments', [LandlordController::class, 'appointments'])->name('landlord.appointments');
    Route::get('/tenants', [LandlordController::class, 'tenants'])->name('landlord.tenants');
    Route::get('/contracts', [LandlordController::class, 'contracts'])->name('landlord.contracts');
    Route::get('/invoices', [LandlordController::class, 'invoices'])->name('landlord.invoices');
    Route::get('/finance', [LandlordController::class, 'finance'])->name('landlord.finance');
    Route::get('/services', [LandlordController::class, 'services'])->name('landlord.services');
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
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return back();
    })->name('notifications.read');
});
require __DIR__ . '/auth.php';

