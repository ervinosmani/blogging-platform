<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostListPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_post_list_does_not_expose_author_email(): void
    {
        $author = User::factory()->create([
            'email' => 'private-author@example.com',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Post::create([
            'title' => 'Public Privacy Test',
            'slug' => 'public-privacy-test',
            'content' => 'Public post content.',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Public Privacy Test',
            ])
            ->assertDontSee($author->email);
    }

    public function test_category_filter_does_not_expose_author_email(): void
    {
        $author = User::factory()->create([
            'email' => 'private-category-author@example.com',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Post::create([
            'title' => 'Technology Privacy Test',
            'slug' => 'technology-privacy-test',
            'content' => 'Testing category privacy.',
            'user_id' => $author->id,
            'category_id' => $categoryId,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->getJson('/api/posts/category/Technology')
            ->assertOk()
            ->assertJsonPath(
                'data.0.title',
                'Technology Privacy Test'
            )
            ->assertJsonPath(
                'data.0.user.name',
                $author->name
            )
            ->assertJsonMissingPath('data.0.user.email')
            ->assertDontSee($author->email);
    }

    public function test_search_returns_only_matching_published_posts(): void
    {
        $author = User::factory()->create([
            'email' => 'private-search@example.com',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['Public Security Guide', 'public-security', 'published'],
            ['Private Security Notes', 'private-security', 'draft'],
            ['Cooking Guide', 'cooking-guide', 'published'],
        ] as [$title, $slug, $status]) {
            Post::create([
                'title' => $title,
                'slug' => $slug,
                'content' => 'Test content',
                'user_id' => $author->id,
                'category_id' => $categoryId,
                'status' => $status,
                'published_at' => $status === 'published'
                    ? now()
                    : null,
            ]);
        }

        $this->getJson('/api/posts/search?query=Security')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Public Security Guide')
            ->assertDontSee('Private Security Notes')
            ->assertDontSee('Cooking Guide')
            ->assertDontSee($author->email);
    }
}
