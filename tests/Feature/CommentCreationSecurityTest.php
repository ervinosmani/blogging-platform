<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentCreationSecurityTest extends TestCase
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
            'category_id' => $categoryId,
            'user_id' => $author->id,
            'status' => $status,
            'published_at' => $status === 'published'
                ? now()
                : null,
        ]);
    }

    public function test_user_cannot_comment_on_another_users_draft(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = $this->createPost($author, 'private-draft', 'draft');

        Sanctum::actingAs($otherUser);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Unauthorized comment',
        ])->assertNotFound();

        $this->assertDatabaseMissing('comments', [
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_author_can_comment_on_own_draft(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author, 'own-draft', 'draft');

        Sanctum::actingAs($author);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'My draft comment',
        ])->assertCreated();
    }

    public function test_reply_cannot_reference_another_posts_comment(): void
    {
        $author = User::factory()->create();

        $firstPost = $this->createPost($author, 'first-post');
        $secondPost = $this->createPost($author, 'second-post');

        $parent = Comment::create([
            'post_id' => $firstPost->id,
            'user_id' => $author->id,
            'content' => 'Original comment',
        ]);

        Sanctum::actingAs($author);

        $this->postJson("/api/posts/{$secondPost->id}/comments", [
            'content' => 'Invalid reply',
            'parent_id' => $parent->id,
        ])->assertUnprocessable();
    }

    public function test_comment_on_nonexistent_post_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/posts/999999/comments', [
            'content' => 'Comment on missing post',
        ])->assertNotFound();
    }

    public function test_valid_reply_can_be_created(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author, 'public-post');

        $parent = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'Parent comment',
        ]);

        Sanctum::actingAs($author);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Valid reply',
            'parent_id' => $parent->id,
        ])->assertCreated();

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'content' => 'Valid reply',
        ]);
    }
}
