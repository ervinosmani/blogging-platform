<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Krijon nje koment te ri per nje post
    public function store(Request $request, $postId)
    {
        $request->validate([
            // 'post_id' => 'required|exists:posts,id',
            'content' => 'required|string',
        ]);

        $user = auth()->user(); 
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $comment = Comment::create([
            'post_id' => $postId, // merret nga URL
            'user_id' => $user->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id ?? null,
        ]);

        return response()->json($comment, 201);
    }

    // Merr te gjitha komentet per nje post te caktuar
    public function index($postId)
    {
        $comments = Comment::with(['user', 'replies.user'])
            ->where('post_id', $postId)
            ->whereNull('parent_id') // vetem komentet e para
            ->latest()
            ->get()
            ->map(function ($comment) {
                $comment->liked = $comment->likes()
                    ->where('user_id', auth()->id())
                    ->exists();
    
                foreach ($comment->replies as $reply) {
                    $reply->liked = $reply->likes()
                        ->where('user_id', auth()->id())
                        ->exists();
                }
    
                return $comment;
            });
    
        return response()->json($comments, 200);
    }    

    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $comment = Comment::find($id);

        if(!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Kontrollo nese perdoruesi i autentikuar eshte autori i komentit
        if(auth()->id() !== $comment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->update(['content' => $request->content]);

        return response()->json(['message' => 'Comment updated successfully','comment' => $comment], 200);
    }

    public function destroy($id) 
    {
        $comment = Comment::find($id);

        if(!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Kontrollo nese perdoruesi i autentikuar eshte autori i komentit
        if (auth()->id() !== $comment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully'], 200);
    }

    public function likeComment($id)
    {
        $comment = Comment::findOrFail($id);
        $user = auth()->user();

        $existingLike = CommentLike::where('comment_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            // Unlike nese ekziston
            $existingLike->delete();
            $comment->decrement('likes');
            return response()->json([
                'message' => 'Like removed',
                'likes' => $comment->likes,
            ]);
        } else {
            // Like nese ska bere me heret
            CommentLike::create([
                'comment_id' => $id,
                'user_id' => $user->id,
            ]);
            $comment->increment('likes');
            return response()->json([
                'message' => 'Comment liked successfully',
                'likes' => $comment->likes,
            ]);
        }
    }

    public function toggleLike($id)
    {
        $userId = auth()->id();

        $like = CommentLike::where('user_id', $userId)
            ->where('comment_id', $id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            CommentLike::create([
                'user_id' => $userId,
                'comment_id' => $id,
            ]);
            $liked = true;
        }

        $count = CommentLike::where('comment_id', $id)->count();

        return response()->json([
            'liked' => $liked,
            'likes' => $count 
        ]);
    }
}
