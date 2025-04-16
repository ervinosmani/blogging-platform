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
        $user = $request->user();

        $query = Post::query();

        if ($request->has('mine')) {
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $query->where('user_id', $user->id);
        } else {
            $query->where('status', 'published');
        }

        // Marrim postet me user-in + numrin e likes dhe komenteve
        $posts = $query
            ->with(['user', 'category'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(6);

        // Shtojme liked_by_user per çdo post ne koleksion
        $posts->getCollection()->transform(function ($post) use ($user) {
            $post->liked_by_user = $user
                ? $post->likes()->where('user_id', $user->id)->exists()
                : false;
            return $post;
        });

        return response()->json($posts);
    }

    // Kerkon nje postim specifik nga slug
    public function show($slug)
    {
        $post = Post::with(['user', 'category'])->where('slug', $slug)->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $liked = auth('sanctum')->check()
            ? \App\Models\PostLike::where('post_id', $post->id)->where('user_id', auth()->id())->exists()
            : false;

        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'image' => $post->image,
            'created_at' => $post->created_at,
            'status' => $post->status,
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'email' => $post->user->email,
            ],
            'likes' => $post->likes()->count(),
            'liked_by_user' => $liked,

            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name
            ] : null,
        ]);
    }

    // Krijo nje postim te ri
    public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'category_id' => 'required|exists:categories,id', // ✅ ndryshim
        'image' => 'nullable|image|max:2048',
        'is_published' => 'nullable|boolean',
    ]);

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('posts', 'public');
    }

    $post = Post::create([
        'title' => $request->title,
        'slug' => Str::slug($request->title),
        'content' => $request->content,
        'category_id' => $request->category_id,
        'user_id' => Auth::id(),
        'image' => $imagePath,
        'status' => $request->is_published ? 'published' : 'draft',
        'published_at' => $request->is_published ? now() : null,
    ]);

    return response()->json($post, 201);
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

        // Sigurohu qe user-i eshte autentikuar
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Merr numrin e postimeve per faqe nga query string, default 10
        $perPage = $request->input('per_page', 10);

        // Kthe postimet e perdoruesit me numrin e likes per secilin post
        $posts = $user->posts()
            ->withCount('likes') // kjo shton kolonen `likes_count` per çdo post
            ->latest()
            ->paginate($perPage);

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
        $post->fill($request->only(['title', 'content', 'category_id']));

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

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== auth()->id()) {
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
                    ->orWhereHas('category', function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%");
                    });                    
            })
            ->paginate(5);

        return response()->json($posts, 200);
    }

    public function filterByCategory($name)
    {
        $posts = Post::where('status', 'published')
            ->whereHas('category', function ($q) use ($name) {
                $q->where('name', $name);
            })
            ->with(['category', 'user'])
            ->paginate(5);

        return response()->json($posts, 200);
    }
}
