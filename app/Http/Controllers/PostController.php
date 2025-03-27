<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // Kthe te gjitha postimet
    public function index(Request $request)
    {
        $query = Post::query();

        if ($request->has('mine')) {
            if (!$request->user()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $query->where('user_id', $request->user()->id);
        } else {
            $query->where('status', 'published');
        }

        return response()->json($query->latest()->paginate(5));
    }

    // Kerkon nje postim specifik nga slug
    public function show($slug)
    {
        $post = Post::where('slug', $slug)->first();

        if(!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json($post, 200);
    }

    // Krijo nje postim te ri
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
            'category' => 'required|string',
            'image' => 'nullable|image|max:2048',
            // 'user_id' => 'required|exists:users,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $post = Post::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'category' => $request->category,
            'user_id' => Auth::id(),
            'image' => $imagePath,
            'status' => $request->is_published ? 'published' : 'draft',
            'published_at' => $request->is_published ? now() : null,
        ]);

        return response()->json($post, 201);
    }

    //Shton nje pelqim ne postim
    public function likePost($slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->increment('likes');
        return response()->json(['message' => 'Post liked successfully', 'likes' => $post->likes], 200);
    }

    // Publikon nje postim
    public function publishPost($slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->update(['status' => 'published', 'published_at' => now()]);
        return response()->json(['message' => 'Post published successfully', 'post' => $post], 200);
    }

    public function myPosts(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $posts = $user->posts()->latest()->paginate(5);

        return response()->json($posts);
    }

    // // Shfaq nje postim te caktuar nga ID
    // public function show(string $id)
    // {
    //     $post = Post::find($id);

    //     if(!$post) {
    //         return response()->json(['message' => 'Post not found'], 404);
    //     }

    //     return response()->json($post, 200);
    // }

    //Perditeso nje postim te caktuar
    public function update(Request $request, string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update fusha te lejuara
        $post->fill($request->only(['title', 'content', 'category']));

        // Rifresko slug nese titulli ka ndryshuar
        if ($request->has('title')) {
            $post->slug = Str::slug($request->title);
        }

        // Nese ka imazh te ri
        if ($request->hasFile('image')) {
            $post->image = $request->file('image')->store('posts', 'public');
        }

        $post->save();

        return response()->json($post, 200);
    }

    //Fshij nje postim
    public function destroy(string $id)
    {
        $post = Post::find($id);

        if(!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully'], 200);
    }

    // Kerkon postime bazuar ne fjalen kyce
    public function search(Request $request)
    {
        $query = $request->input('query');

        $posts = Post::where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->paginate(5);

        return response()->json($posts, 200);
    }

    public function filterByCategory($name)
    {
        $posts = Post::where('status', 'published')
            ->where('category', $name)
            ->paginate(5);

        return response()->json($posts, 200);
    }
}
