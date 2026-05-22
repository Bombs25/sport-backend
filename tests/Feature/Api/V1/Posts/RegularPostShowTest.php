<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegularPostShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertRegularPost(int $authorId, array $overrides = []): int
    {
        return DB::table('posts')->insertGetId(array_merge([
            'user_id' => $authorId,
            'body' => 'Post de test',
            'visibility' => 'public',
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_viewer_can_fetch_a_public_regular_post(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id);

        DB::table('post_media')->insert([
            'post_id' => $postId,
            'position' => 0,
            'path' => 'posts/photo.jpg',
            'blurhash' => null,
            'alt_text' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.id', $postId)
            ->assertJsonPath('data.user_id', $author->id)
            ->assertJsonPath('data.publication_type', 'regular')
            ->assertJsonPath('data.viewer_has_liked', false)
            ->assertJsonCount(1, 'data.media');
    }

    public function test_viewer_has_liked_is_true_when_like_row_exists(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id);

        DB::table('post_likes')->insert([
            'users_id' => $viewer->id,
            'publication_id' => $postId,
            'publication_type' => 'regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.viewer_has_liked', true);
    }

    public function test_unknown_post_returns_404(): void
    {
        $viewer = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/posts/regular/999999', [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_non_published_post_returns_404(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id, ['status' => 'draft', 'published_at' => null]);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_soft_deleted_post_returns_404(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id, ['deleted_at' => now()]);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_followers_only_post_is_hidden_from_non_follower(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id, ['visibility' => 'followers']);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_followers_only_post_is_visible_to_an_accepted_follower(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id, ['visibility' => 'followers']);

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $author->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.id', $postId);
    }

    public function test_author_can_fetch_their_own_followers_only_post(): void
    {
        $author = User::factory()->create();
        $token = $author->createToken('auth')->plainTextToken;

        $postId = $this->insertRegularPost($author->id, ['visibility' => 'followers']);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.id', $postId);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId)
            ->assertUnauthorized();
    }
}
