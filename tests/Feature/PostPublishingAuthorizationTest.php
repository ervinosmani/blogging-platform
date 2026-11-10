<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostPublishingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createDraftPost(User $author): Post
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Post::create([
            'title' => 'My Draft Post',
            'slug' => 'my-draft-post',
            'content' => 'This post is not published yet.',
            'category_id' => $categoryId,
            'user_id' => $author->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_author_can_publish_own_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createDraftPost($author);

        Sanctum::actingAs($author);

        $this->postJson("/api/posts/{$post->slug}/publish")
            ->assertOk()
            ->assertJsonPath('post.status', 'published');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $this->assertNotNull(
            $post->fresh()->published_at
        );
    }

    public function test_another_user_cannot_publish_post(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = $this->createDraftPost($author);

        Sanctum::actingAs($otherUser);

        $this->postJson("/api/posts/{$post->slug}/publish")
            ->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_guest_cannot_publish_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createDraftPost($author);

        $this->postJson("/api/posts/{$post->slug}/publish")
            ->assertUnauthorized();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'draft',
        ]);
    }
}
