<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostCommentLikeApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_comment_like_persists_immediately(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $commentId = $this->insertComment($postId, 'regular', $author->id, 'Hello');

        $this->actingAs($liker, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/likes", [
                'post_type' => 'regular',
                'action' => 'like',
            ])
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('comments_likes', [
            'users_id' => $liker->id,
            'comment_id' => $commentId,
        ]);
        $this->assertDatabaseHas('comments', [
            'id' => $commentId,
            'likes_count' => 1,
        ]);
    }

    public function test_user_can_like_own_comment(): void
    {
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $commentId = $this->insertComment($postId, 'regular', $author->id, 'Mon commentaire');

        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/likes", [
                'post_type' => 'regular',
                'action' => 'like',
            ])
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('comments_likes', [
            'users_id' => $author->id,
            'comment_id' => $commentId,
        ]);
    }

    public function test_comment_dislike_removes_like(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $commentId = $this->insertComment($postId, 'regular', $author->id, 'Hello');

        $this->actingAs($liker, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/likes", [
                'post_type' => 'regular',
                'action' => 'like',
            ])
            ->assertOk();

        $this->actingAs($liker, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/likes", [
                'post_type' => 'regular',
                'action' => 'dislike',
            ])
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);

        $this->assertDatabaseMissing('comments_likes', [
            'users_id' => $liker->id,
            'comment_id' => $commentId,
        ]);
    }

    private function insertRegularPost(int $authorId): int
    {
        $now = now();

        return (int) DB::table('posts')->insertGetId([
            'user_id' => $authorId,
            'body' => 'Post',
            'visibility' => 'public',
            'status' => 'published',
            'media_count' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
            'total_shares' => 0,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertComment(int $postId, string $type, int $userId, string $content): int
    {
        return (int) DB::table('comments')->insertGetId([
            'publication_id' => $postId,
            'publication_type' => $type,
            'user_id' => $userId,
            'content' => $content,
            'likes_count' => 0,
            'responses_count' => 0,
            'created_at' => now(),
        ]);
    }
}
