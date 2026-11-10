<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_supports_post_categories(): void
    {
        // Sigurohemi qe nuk po perdorim databazen reale.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );

        // Kontrollojme strukturen pas migrations.
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('sessions'));

        $this->assertTrue(
            Schema::hasColumn('posts', 'category_id')
        );

        $this->assertFalse(
            Schema::hasColumn('posts', 'category')
        );

        // Krijojme nje kategori dhe nje perdorues testues.
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create();

        // Verifikojme qe mund te krijohet postimi.
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Testing post categories.',
            'category_id' => $categoryId,
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'category_id' => $categoryId,
            'user_id' => $user->id,
        ]);
    }
}
