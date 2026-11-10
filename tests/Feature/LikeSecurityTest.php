<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(
        User $author,
        string $slug,
        string $status = 'published'
    ): Post {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Category ' . $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Post::create([
            'title' => 'Test Post ' . $slug,
            'slug' => $slug,
            'content' => 'Test content.',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => $status,
            'published_at' => $status === 'published'
                ? now()
                : null,
        ]);
    }

    public function test_user_cannot_like_another_users_draft(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = $this->createPost(
            $author,
            'private-post',
            'draft'
        );

        Sanctum::actingAs($otherUser);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertNotFound();

        $this->assertDatabaseMissing('post_likes', [
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_author_can_like_own_draft(): void
    {
        $author = User::factory()->create();

        $post = $this->createPost(
            $author,
            'own-draft',
            'draft'
        );

        Sanctum::actingAs($author);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes', 1);
    }

    public function test_user_can_like_published_post(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();

        $post = $this->createPost(
            $author,
            'published-post'
        );

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes', 1);
    }

    public function test_user_cannot_like_comment_on_another_users_draft(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = $this->createPost(
            $author,
            'private-comment-post',
            'draft'
        );

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'Private comment',
        ]);

        Sanctum::actingAs($otherUser);

        $this->postJson("/api/comments/{$comment->id}/like")
            ->assertNotFound();

        $this->assertDatabaseMissing('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_like_on_nonexistent_comment_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/comments/999999/like')
            ->assertNotFound();
    }
}
