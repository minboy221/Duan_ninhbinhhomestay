<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\PublicListingService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    protected CategoryService $categoryService;
    protected PublicListingService $publicListingService;

    public function __construct(CategoryService $categoryService, PublicListingService $publicListingService)
    {
        $this->categoryService = $categoryService;
        $this->publicListingService = $publicListingService;
    }

    public function index()
    {
        // Nếu là Chủ trọ -> Chuyển trực tiếp sang trang Quản lý
        if (auth()->check() && auth()->user()->role === 'landlord') {
            return redirect()->route('landlord.dashboard');
        }

        $categoryData = $this->categoryService->getActiveData();
        
        $featuredRooms = $this->publicListingService->getFeaturedRooms(8);
        $topReviews = $this->publicListingService->getTopReviews(6);
        $systemStats = $this->publicListingService->getSystemStats();

        return Inertia::render('Client/Index', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('signup'),
            'canVerfyEmail' => Route::has('canverfyemail'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'categories' => $categoryData['types'],
            'areas' => $categoryData['areas'],
            'amenities' => $categoryData['amenities'],
            'featuredRooms' => $featuredRooms,
            'topReviews' => $topReviews,
            'systemStats' => $systemStats,
        ]);
    }
}
