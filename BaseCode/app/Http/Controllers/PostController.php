<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Post;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with('author:id,name');

        // Lọc theo tìm kiếm
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        // Lọc theo danh mục
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Phân trang danh sách bài viết
        $posts = $query->orderBy('created_at', 'desc')->paginate(4)->withQueryString();

        // Lấy danh mục bài viết kèm số lượng bài viết tương ứng
        $categories = Post::select('category as name', \DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get();

        // Lấy danh sách bài viết mới nhất cho sidebar
        $recentPosts = Post::orderBy('created_at', 'desc')->limit(5)->get();

        return Inertia::render('Client/tintuc', [
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function show($slug)
    {
        // Lấy thông tin bài viết theo slug
        $post = Post::with('author:id,name')->where('slug', $slug)->firstOrFail();

        // Tăng lượt xem
        $post->increment('views');

        // Lấy danh mục kèm số lượng
        $categories = Post::select('category as name', \DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get();

        // Lấy danh sách bài viết mới nhất cho sidebar
        $recentPosts = Post::orderBy('created_at', 'desc')->limit(5)->get();

        return Inertia::render('Client/chitiettintuc', [
            'post' => $post,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
        ]);
    }

    public function suggest(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            return response()->json([]);
        }

        $suggestions = Post::select('id', 'title', 'slug')
            ->where('title', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        return response()->json($suggestions);
    }
}
