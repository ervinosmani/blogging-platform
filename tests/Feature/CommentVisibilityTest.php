<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function createPostWithComment(
        string $status = 'draft'
    ): array {
        $author = User::factory()->create();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Post content.',
            'category_id' => $categoryId,
            'user_id' => $author->id,
            'status' => $status,
            'published_at' => $status === 'published'
                ? now()
                : null,
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'Test comment.',
        ]);

        return [$author, $post];
    }

    public function test_guest_cannot_view_draft_comments(): void
    {
        [, $post] = $this->createPostWithComment();

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertNotFound();
    }

    public function test_another_user_cannot_view_draft_comments(): void
    {
        [, $post] = $this->createPostWithComment();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertNotFound();
    }

    public function test_author_can_view_own_draft_comments(): void
    {
        [$author, $post] = $this->createPostWithComment();

        Sanctum::actingAs($author);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('0.content', 'Test comment.');
    }

    public function test_public_comments_do_not_expose_email(): void
    {
        [, $post] = $this->createPostWithComment('published');

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('0.content', 'Test comment.')
            ->assertJsonMissingPath('0.user.email');
    }
}
