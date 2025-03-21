<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // Kthe te gjitha postimet
    public function index()
    {
        return response()->json(Post::all(), 200);
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

        $post = Post::create($request->all());

        return response()->json($post, 201);
    }

    // Shfaq nje postim te caktuar nga ID
    public function show(string $id)
    {
        $post = Post::find($id);

        if(!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json($post, 200);
    }

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
