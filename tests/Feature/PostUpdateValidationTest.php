<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_cannot_be_updated_with_invalid_category(): void
    {
        $author = User::factory()->create();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $post = Post::create([
            'title' => 'Original Post',
            'slug' => 'original-post',
            'content' => 'Original content',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => 'draft',
        ]);

        Sanctum::actingAs($author);

        $this->putJson("/api/posts/{$post->id}", [
            'category_id' => 999999,
        ])
            ->assertUnprocessable();

        // Kategoria origjinale duhet te mbetet e pandryshuar.
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'category_id' => $categoryId,
        ]);
    }
}
