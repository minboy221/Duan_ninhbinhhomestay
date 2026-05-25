<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LandlordController;
use Illuminate\Foundation\Application;
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
Route::get('/', function () {
    return Inertia::render('Client/Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('signup'),
        'canVerfyEmail' => Route::has('canverfyemail'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Route cho Trang Giới thiệu
Route::get('/about', function () {
    return Inertia::render('Client/About'); // Trỏ đến file Pages/Client/About.vue
})->name('about');

// Route cho Trang Tìm trọ
Route::get('/timtro', function () {
    return Inertia::render('Client/timtro'); // Trỏ đến file Pages/Client/About.vue
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
    Route::get('/quanlynoio', [ProfileController::class, 'quanlynoio'])->name('quanlynoio');
    Route::get('/lichsuthanhtoan', [ProfileController::class, 'lichsuthanhtoan'])->name('lichsuthanhtoan');
    Route::get('/caidatuser', [ProfileController::class, 'caidatuser'])->name('caidatuser');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ROUTER cho admin
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard',  [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/users',      [AdminController::class, 'users'])->name('admin.users');
    Route::get('/landlords',  [AdminController::class, 'landlords'])->name('admin.landlords');
    Route::get('/approval',   [AdminController::class, 'approval'])->name('admin.approval');
    Route::get('/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('/reports',    [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/reviews',    [AdminController::class, 'reviews'])->name('admin.reviews');
    Route::get('/revenue',    [AdminController::class, 'revenue'])->name('admin.revenue');
    Route::get('/roles',      [AdminController::class, 'roles'])->name('admin.roles');
    Route::get('/auditlog',   [AdminController::class, 'auditlog'])->name('admin.auditlog');
    Route::get('/website',    [AdminController::class, 'website'])->name('admin.website');
    Route::get('/ads',        [AdminController::class, 'ads'])->name('admin.ads');
});

// ROUTER cho landlord (chủ trọ)
Route::middleware(['auth', 'landlord'])->prefix('landlord')->group(function () {
    Route::get('/dashboard',    [LandlordController::class, 'dashboard'])->name('landlord.dashboard');
    Route::get('/profile',      [LandlordController::class, 'profile'])->name('landlord.profile');
    Route::get('/rooms',        [LandlordController::class, 'rooms'])->name('landlord.rooms');
    Route::get('/listings',     [LandlordController::class, 'listings'])->name('landlord.listings');
    Route::get('/listings/create', [LandlordController::class, 'listingCreate'])->name('landlord.listings.create');
    Route::get('/appointments', [LandlordController::class, 'appointments'])->name('landlord.appointments');
    Route::get('/tenants',      [LandlordController::class, 'tenants'])->name('landlord.tenants');
    Route::get('/contracts',    [LandlordController::class, 'contracts'])->name('landlord.contracts');
    Route::get('/invoices',     [LandlordController::class, 'invoices'])->name('landlord.invoices');
    Route::get('/finance',      [LandlordController::class, 'finance'])->name('landlord.finance');
});

require __DIR__ . '/auth.php';

