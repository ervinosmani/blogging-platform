<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Krijon nje koment te ri per nje post
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'content' => 'required|string',
        ]);

        $user = auth()->user(); 
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $comment = Comment::create([
            'post_id' => $request->post_id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        return response()->json($comment, 201);
    }

    // Merr te gjitha komentet per nje post te caktuar
    public function index($postId)
    {
        return response()->json(Comment::where('post_id', $postId)->get(), 200);
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
}
