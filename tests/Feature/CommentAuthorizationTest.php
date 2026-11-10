<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createComment(): array
    {
        $author = User::factory()->create();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $post = Post::create([
            'title' => 'Authorization Test Post',
            'slug' => 'authorization-test-post',
            'content' => 'Test content',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'content' => 'Original comment',
        ]);

        return [$author, $comment];
    }

    public function test_author_can_update_own_comment(): void
    {
        [$author, $comment] = $this->createComment();

        Sanctum::actingAs($author);

        $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Updated comment',
        ])
            ->assertOk()
            ->assertJsonPath(
                'comment.content',
                'Updated comment'
            );

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment',
        ]);
    }

    public function test_other_user_cannot_update_comment(): void
    {
        [, $comment] = $this->createComment();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Unauthorized update',
        ])->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Original comment',
        ]);
    }

    public function test_author_can_delete_own_comment(): void
    {
        [$author, $comment] = $this->createComment();

        Sanctum::actingAs($author);

        $this->deleteJson("/api/comments/{$comment->id}")
            ->assertOk();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_other_user_cannot_delete_comment(): void
    {
        [, $comment] = $this->createComment();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $this->deleteJson("/api/comments/{$comment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_guest_cannot_update_or_delete_comment(): void
    {
        [, $comment] = $this->createComment();

        $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Guest update',
        ])->assertUnauthorized();

        $this->deleteJson("/api/comments/{$comment->id}")
            ->assertUnauthorized();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Original comment',
        ]);
    }
}
