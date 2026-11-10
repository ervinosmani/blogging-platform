<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\UniqueConstraintViolationException;

class PostController extends Controller
{
    // Kthe te gjitha postimet
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Post::query();

        if ($request->has('mine')) {
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query->where('user_id', $user->id);
        } else {
            $query->where('status', 'published');
        }

        $posts = $query
            ->with(['user:id,name', 'category'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(6);

        $posts->getCollection()->transform(function ($post) use ($user) {
            $post->liked_by_user = $user
                ? $post->likes()
                    ->where('user_id', $user->id)
                    ->exists()
                : false;

            return $post;
        });

        return response()->json($posts);
    }

    // Shfaq nje postim sipas slug
    public function show(Request $request, string $slug)
    {
        $post = Post::with(['user:id,name', 'category'])
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Merr perdoruesin nese eshte i autentikuar
        $user = $request->user('sanctum');

        // Draftet mund te lexohen vetem nga autori
        if (
            $post->status !== 'published' &&
            (!$user || (int) $post->user_id !== (int) $user->id)
        ) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Kontrollo nese perdoruesi e ka pelqyer postimin
        $liked = $user
            ? $post->likes()
                ->where('user_id', $user->id)
                ->exists()
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
            ],
            'likes' => $post->likes()->count(),
            'liked_by_user' => $liked,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
            ] : null,
        ]);
    }

    // Krijo nje postim te ri
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_published' => 'nullable|boolean',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('posts', 'public');
        }

        $isPublished = $validated['is_published'] ?? false;

        $attributes = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category_id' => $validated['category_id'],
            'user_id' => $request->user()->id,
            'image' => $imagePath,
            'status' => $isPublished ? 'published' : 'draft',
            'published_at' => $isPublished ? now() : null,
        ];

        // Provo deri ne 3 here nese ka konflikt slug.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $attributes['slug'] = $this->generateUniqueSlug(
                $validated['title']
            );

            try {
                $post = Post::create($attributes);

                return response()->json($post, 201);
            } catch (UniqueConstraintViolationException $e) {
                // Mos fsheh konflikte nga fusha te tjera.
                if (!Post::where('slug', $attributes['slug'])->exists()) {
                    throw $e;
                }
            }
        }

        return response()->json([
            'message' => 'Could not create post. Please try again.',
        ], 409);
    }

    // Publiko nje postim - vetem autori ka leje
    public function publishPost(Request $request, string $slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        if ((int) $post->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        if ($post->status !== 'published') {
            $post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Post published successfully',
            'post' => $post,
        ], 200);
    }

    // Kthe postimet e perdoruesit te autentikuar
    public function myPosts(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $perPage = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:50',
        ])['per_page'] ?? 10;

        $posts = $user->posts()
            ->withCount('likes')
            ->latest()
            ->paginate($perPage);

        return response()->json($posts);
    }

    // Perditeso nje postim
    public function update(Request $request, string $id)
    {
        // Kontrollo nese postimi ekziston.
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Vetem autori mund ta perditesoje postimin.
        if ((int) $post->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        // Valido te dhenat perpara ruajtjes.
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        // Perditeso vetem fushat e lejuara.
        $post->fill(
            $request->only(['title', 'content', 'category_id'])
        );
        // Perditeso slug nese ndryshon titulli.
        if ($post->isDirty('title')) {
            $post->slug = $this->generateUniqueSlug(
                $post->title,
                $post->id
            );
        }

        // Ruaj imazhin e ri nese eshte derguar.
        if ($request->hasFile('image')) {
            $post->image = $request->file('image')
                ->store('posts', 'public');
        }

        $post->save();

        return response()->json($post, 200);
    }

    // Fshij nje postim
    public function destroy(Request $request, string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        if ((int) $post->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ], 200);
    }

    // Kerko postime sipas fjales kyce
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

    // Filtro postimet sipas kategorise
    public function filterByCategory($name)
    {
        $posts = Post::where('status', 'published')
            ->whereHas('category', function ($q) use ($name) {
                $q->where('name', $name);
            })
            ->with(['category', 'user:id,name'])
            ->paginate(5);

        return response()->json($posts, 200);
    }

    private function generateUniqueSlug(
        string $title,
        ?int $excludePostId = null
    ): string {
        $baseSlug = Str::slug($title) ?: 'post';

        // Le hapesire per prapashtesat numerike.
        $baseSlug = substr($baseSlug, 0, 240);

        $slug = $baseSlug;
        $counter = 2;

        // Kontrollo nese slug ekziston ne databaze.
        while (
            Post::query()
                ->where('slug', $slug)
                ->when(
                    $excludePostId !== null,
                    function ($query) use ($excludePostId) {
                        $query->where('id', '!=', $excludePostId);
                    }
                )
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
