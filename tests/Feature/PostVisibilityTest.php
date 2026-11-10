<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(
        User $author,
        string $status = 'draft'
    ): Post {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'This is the post content.',
            'category_id' => $categoryId,
            'user_id' => $author->id,
            'status' => $status,
            'published_at' => $status === 'published'
                ? now()
                : null,
        ]);
    }

    public function test_guest_cannot_view_draft_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        $this->getJson("/api/posts/slug/{$post->slug}")
            ->assertNotFound();
    }

    public function test_another_user_cannot_view_draft_post(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = $this->createPost($author);

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/posts/slug/{$post->slug}")
            ->assertNotFound();
    }

    public function test_author_can_view_own_draft_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost($author);

        Sanctum::actingAs($author);

        $this->getJson("/api/posts/slug/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('status', 'draft');
    }

    public function test_guest_can_view_published_post(): void
    {
        $author = User::factory()->create();

        $post = $this->createPost($author, 'published');

        $this->getJson("/api/posts/slug/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('status', 'published');
    }
}
