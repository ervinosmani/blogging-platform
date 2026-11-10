<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostSlugUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(): int
    {
        return DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_posts_with_same_title_get_unique_slugs(): void
    {
        $author = User::factory()->create();
        $categoryId = $this->createCategory();

        Sanctum::actingAs($author);

        $data = [
            'title' => 'My First Post',
            'content' => 'Test content',
            'category_id' => $categoryId,
        ];

        $this->postJson('/api/posts', $data)
            ->assertCreated()
            ->assertJsonPath('slug', 'my-first-post');

        $this->postJson('/api/posts', $data)
            ->assertCreated()
            ->assertJsonPath('slug', 'my-first-post-2');

        $this->assertDatabaseCount('posts', 2);
    }

    public function test_updating_title_does_not_create_duplicate_slug(): void
    {
        $author = User::factory()->create();
        $categoryId = $this->createCategory();

        $firstPost = Post::create([
            'title' => 'Existing Title',
            'slug' => 'existing-title',
            'content' => 'First post',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => 'draft',
        ]);

        $secondPost = Post::create([
            'title' => 'Different Title',
            'slug' => 'different-title',
            'content' => 'Second post',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => 'draft',
        ]);

        Sanctum::actingAs($author);

        $this->putJson("/api/posts/{$secondPost->id}", [
            'title' => 'Existing Title',
        ])
            ->assertOk()
            ->assertJsonPath('slug', 'existing-title-2');

        $this->assertDatabaseHas('posts', [
            'id' => $firstPost->id,
            'slug' => 'existing-title',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $secondPost->id,
            'slug' => 'existing-title-2',
        ]);
    }

    public function test_third_duplicate_slug_and_content_update(): void
    {
        $author = User::factory()->create();
        $categoryId = $this->createCategory();

        Sanctum::actingAs($author);

        $data = [
            'title' => 'My First Post',
            'content' => 'Original content',
            'category_id' => $categoryId,
        ];

        $this->postJson('/api/posts', $data)
            ->assertCreated()
            ->assertJsonPath('slug', 'my-first-post');

        $this->postJson('/api/posts', $data)
            ->assertCreated()
            ->assertJsonPath('slug', 'my-first-post-2');

        $third = $this->postJson('/api/posts', $data)
            ->assertCreated()
            ->assertJsonPath('slug', 'my-first-post-3');

        // Editimi i permbajtjes nuk duhet ta ndryshoje slug.
        $this->putJson("/api/posts/{$third->json('id')}", [
            'content' => 'Updated content',
        ])
            ->assertOk()
            ->assertJsonPath('slug', 'my-first-post-3');

        $this->assertDatabaseCount('posts', 3);
    }
}
