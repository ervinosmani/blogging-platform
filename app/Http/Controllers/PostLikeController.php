<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function toggle(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Kontrollo nese postimi ekziston.
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Vetem autori mund te beje like ne draftin e vet.
        if (
            $post->status !== 'published' &&
            (int) $post->user_id !== (int) $user->id
        ) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Kontrollo nese perdoruesi e ka pelqyer postimin.
        $like = PostLike::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($like) {
            // Unlike: largo pelqimin ekzistues.
            $like->delete();
            $liked = false;
        } else {
            // Like: krijo nje pelqim te ri.
            PostLike::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);

            $liked = true;
        }

        // Numri i pelqimeve merret nga databaza.
        $count = PostLike::where('post_id', $post->id)->count();

        return response()->json([
            'liked' => $liked,
            'likes' => $count,
        ], 200);
    }
}
