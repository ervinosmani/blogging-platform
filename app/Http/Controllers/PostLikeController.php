<?php

namespace App\Http\Controllers;

use App\Models\PostLike;
use App\Models\Post;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function toggle($id)
    {
        $userId = auth()->id();

        $post = Post::find($id); // kontrollon nëse ekziston
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $like = PostLike::where('user_id', $userId)
                        ->where('post_id', $id)
                        ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            PostLike::create([
                'user_id' => $userId,
                'post_id' => $id
            ]);
            $liked = true;
        }

        $count = PostLike::where('post_id', $id)->count();

        return response()->json([
            'liked' => $liked,
            'likes' => $count
        ]);
    }
}
