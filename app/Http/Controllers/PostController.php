<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    // Kthe te gjitha postimet
    public function index()
    {
        return response()->json(Post::where('status', 'published')->paginate(5), 200);
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
            'user_id' => 'required|exists:users,id',
        ]);

        $post = Post::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'category' => $request->category,
            'user_id' => $request->user_id,
            'status' => 'draft',
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

        if(!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->update($request->all());

        return response()->json($post, 200);
    }

    //Fshij nje postim
    public function destroy(string $id)
    {
        $post = Post::find($id);

        if(!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully'], 200);
    }
}
