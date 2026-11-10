<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    // Krijon nje koment ose reply per nje postim
    public function store(Request $request, string $postId)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Kontrollo nese postimi ekziston
        $post = Post::find($postId);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Vetem autori mund te komentoje ne draftin e vet
        if (
            $post->status !== 'published' &&
            (int) $post->user_id !== (int) $user->id
        ) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Parent comment duhet t'i perkase te njejtit postim
        $validated = $request->validate([
            'content' => [
                'required',
                'string',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')
                    ->where('post_id', $post->id),
            ],
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json($comment, 201);
    }

    // Merr komentet duke respektuar privatesine e postimit
    public function index(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $user = $request->user('sanctum');

        // Komentet e draftit i lexon vetem autori
        if (
            $post->status !== 'published' &&
            (!$user || (int) $post->user_id !== (int) $user->id)
        ) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Mos ekspozo email-et e komentuesve
        $comments = Comment::with([
            'user:id,name',
            'replies.user:id,name',
        ])
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        $userId = $user?->id;

        $comments->transform(function ($comment) use ($userId) {
            $comment->likes = $comment->likes()->count();

            $comment->liked = $userId
                ? $comment->isLikedBy($userId)
                : false;

            $comment->replies->transform(function ($reply) use ($userId) {
                $reply->likes = $reply->likes()->count();

                $reply->liked = $userId
                    ? $reply->isLikedBy($userId)
                    : false;

                return $reply;
            });

            return $comment;
        });

        return response()->json($comments, 200);
    }

    // Perditeson nje koment ekzistues
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        if ((int) auth()->id() !== (int) $comment->user_id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $comment->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment,
        ], 200);
    }

    // Fshin nje koment
    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        if ((int) auth()->id() !== (int) $comment->user_id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ], 200);
    }

    // Metode e vjeter - per shqyrtim gjate pastrimit
    public function likeComment($id)
    {
        $comment = Comment::findOrFail($id);
        $user = auth()->user();

        $existingLike = CommentLike::where('comment_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $comment->decrement('likes');

            return response()->json([
                'message' => 'Like removed',
                'likes' => $comment->likes,
            ]);
        }

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

    // Shton ose largon like nga komenti
    public function toggleLike(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Kontrollo nese komenti ekziston.
        $comment = Comment::with('post')->find($id);

        if (!$comment || !$comment->post) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $post = $comment->post;

        // Komentet e draftit jane private pervec autorit.
        if (
            $post->status !== 'published' &&
            (int) $post->user_id !== (int) $user->id
        ) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $like = CommentLike::where('user_id', $user->id)
            ->where('comment_id', $comment->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            CommentLike::create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
            ]);

            $liked = true;
        }

        $count = CommentLike::where(
            'comment_id',
            $comment->id
        )->count();

        return response()->json([
            'liked' => $liked,
            'likes' => $count,
        ], 200);
    }
}
