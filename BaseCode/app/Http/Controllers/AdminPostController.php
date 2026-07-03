<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminPostController extends Controller
{
    public function index()
    {
        $posts = Post::with('author:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'image' => $post->image,
                    'summary' => $post->summary,
                    'category' => $post->category,
                    'tags' => $post->tags,
                    'views' => $post->views,
                    'author_name' => $post->author ? $post->author->name : 'Admin',
                    'created_at' => $post->created_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'category' => 'required|string|max:255',
            'tags' => 'nullable|string',
            'image_file' => 'nullable|image|max:2048',
            'image_url' => 'nullable|string',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'category.required' => 'Vui lòng chọn danh mục bài viết.',
            'image_file.image' => 'File tải lên phải là ảnh.',
            'image_file.max' => 'Dung lượng ảnh tối đa 2MB.',
        ]);

        $slug = Str::slug($request->title);
        $originalSlug = $slug;
        $count = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $imagePath = null;
        if ($request->hasFile('image_file')) {
            $imagePath = '/storage/' . $request->file('image_file')->store('posts', 'public');
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        } else {
            $imagePath = '/anh/banner_tro.png';
        }

        Post::create([
            'title' => $request->title,
            'slug' => $slug,
            'image' => $imagePath,
            'summary' => $request->summary,
            'content' => $request->content,
            'category' => $request->category,
            'tags' => $request->tags,
            'author_id' => Auth::id() ?? 1,
            'views' => 0,
        ]);

        return redirect()->route('admin.posts.index')->with('success', 'Tạo bài viết mới thành công.');
    }

    public function edit($id)
    {
        $post = Post::findOrFail($id);
        return Inertia::render('Admin/Posts/Edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'category' => 'required|string|max:255',
            'tags' => 'nullable|string',
            'image_file' => 'nullable|image|max:2048',
            'image_url' => 'nullable|string',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'category.required' => 'Vui lòng chọn danh mục bài viết.',
            'image_file.image' => 'File tải lên phải là ảnh.',
            'image_file.max' => 'Dung lượng ảnh tối đa 2MB.',
        ]);

        if ($post->title !== $request->title) {
            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $count = 1;
            while (Post::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $post->slug = $slug;
        }

        if ($request->hasFile('image_file')) {
            if ($post->image && str_starts_with($post->image, '/storage/posts/')) {
                $oldPath = str_replace('/storage/', '', $post->image);
                Storage::disk('public')->delete($oldPath);
            }
            $post->image = '/storage/' . $request->file('image_file')->store('posts', 'public');
        } elseif ($request->filled('image_url')) {
            $post->image = $request->input('image_url');
        }

        $post->title = $request->title;
        $post->summary = $request->summary;
        $post->content = $request->content;
        $post->category = $request->category;
        $post->tags = $request->tags;
        $post->save();

        return redirect()->route('admin.posts.index')->with('success', 'Cập nhật bài viết thành công.');
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        if ($post->image && str_starts_with($post->image, '/storage/posts/')) {
            $oldPath = str_replace('/storage/', '', $post->image);
            Storage::disk('public')->delete($oldPath);
        }

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Đã xóa bài viết thành công.');
    }
}
