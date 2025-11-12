<?php

namespace App\Http\Controllers\Api;

use App\CustomFunction\CustomFunction;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Blog::with('user:id,name,email')->withLikesCount();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $query->search($request->search);
        }

        // Sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'most_liked':
                    $query->mostLiked();
                    break;
                case 'latest':
                    $query->latest();
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 10);
        $blogs = $query->paginate($perPage);

        // Add is_liked_by_user flag
        $userId = auth()->id();
        $blogs->getCollection()->transform(function ($blog) use ($userId) {
            $blog->is_liked_by_user = $blog->isLikedBy($userId);
            $blog->image_url = $blog->image ? asset('public/uploads/blogs/' . $blog->image) : null;
            return $blog;
        });

        return response()->json([
            'success' => true,
            'message' => 'Blogs retrieved successfully',
            'data' => $blogs,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
        ];

        // Save image
        if ($request->hasFile('image')) {
            $fileName = CustomFunction::fileUpload($request->file('image'), $request->image, 'blogs');
            $data['image'] = $fileName;
        }

        $blog = Blog::create($data);
        $blog->load('user:id,name,email');
        $blog->loadCount('likes');
        $blog->is_liked_by_user = false;
        $blog->image_url = $blog->image ? asset('public/uploads/blogs/' . $blog->image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Blog created successfully',
            'data' => $blog,
        ], 201);
    }

    public function show($id)
    {
        $blog = Blog::with('user:id,name,email')->withLikesCount()->find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        $blog->is_liked_by_user = $blog->isLikedBy(auth()->id());
        $blog->image_url = $blog->image ? asset('public/uploads/blogs/' . $blog->image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Blog retrieved successfully',
            'data' => $blog,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        // Check if the authenticated user is the owner
        if ($blog->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this blog',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('title')) {
            $blog->title = $request->title;
        }

        if ($request->has('description')) {
            $blog->description = $request->description;
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($blog->image) {
                CustomFunction::removeFile($blog->image, 'blogs');
            }

            $fileName = CustomFunction::fileUpload($request->file('image'), $request->image, 'blogs');
            $blog->image = $fileName;
        }

        $blog->save();
        $blog->load('user:id,name,email');
        $blog->loadCount('likes');
        $blog->is_liked_by_user = $blog->isLikedBy(auth()->id());
        $blog->image_url = $blog->image ? asset('public/uploads/blogs/' . $blog->image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Blog updated successfully',
            'data' => $blog,
        ], 200);
    }

    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        // Check if the authenticated user is the owner
        if ($blog->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this blog',
            ], 403);
        }

        // Delete image if exists
        if ($blog->image) {
            CustomFunction::removeFile($blog->image, 'blogs');
        }

        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog deleted successfully',
        ], 200);
    }

    public function toggleLike($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        $userId = auth()->id();
        $like = $blog->likes()->where('user_id', $userId)->first();

        if ($like) {
            // Unlike
            $like->delete();
            $message = 'Blog unliked successfully';
            $isLiked = false;
        } else {
            // Like
            $blog->likes()->create([
                'user_id' => $userId,
            ]);
            $message = 'Blog liked successfully';
            $isLiked = true;
        }

        $blog->loadCount('likes');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'blog_id' => $blog->id,
                'is_liked' => $isLiked,
                'likes_count' => $blog->likes_count,
            ],
        ], 200);
    }
}
