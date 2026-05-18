<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
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
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
});

require __DIR__ . '/auth.php';
