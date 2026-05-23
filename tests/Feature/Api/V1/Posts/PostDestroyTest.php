<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostDestroyTest extends TestCase
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

    private function tokenFor(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }

    public function test_author_can_soft_delete_their_post(): void
    {
        $author = User::factory()->create();
        $token = $this->tokenFor($author);
        $postId = $this->insertRegularPost($author->id);

        $this->deleteJson('/api/v1/auth/posts/'.$postId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertNotNull(DB::table('posts')
            ->where('id', $postId)
            ->value('deleted_at'));
    }

    public function test_non_author_cannot_delete_post(): void
    {
        $author = User::factory()->create();
        $intruder = User::factory()->create();
        $token = $this->tokenFor($intruder);
        $postId = $this->insertRegularPost($author->id);

        $this->deleteJson('/api/v1/auth/posts/'.$postId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();

        $this->assertNull(DB::table('posts')
            ->where('id', $postId)
            ->value('deleted_at'));
    }

    public function test_delete_unknown_post_returns_422(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->deleteJson('/api/v1/auth/posts/999999', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['post_id']);
    }

    public function test_delete_already_deleted_post_returns_422(): void
    {
        $author = User::factory()->create();
        $token = $this->tokenFor($author);
        $postId = $this->insertRegularPost($author->id, [
            'deleted_at' => now()->subMinute(),
        ]);

        $this->deleteJson('/api/v1/auth/posts/'.$postId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['post_id']);
    }

    public function test_delete_requires_authentication(): void
    {
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $this->deleteJson('/api/v1/auth/posts/'.$postId)
            ->assertUnauthorized();
    }
}
