<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostCommentResponseStoreApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_nested_response_to_reply_uses_is_reponse_to_main_comment_false(): void
    {
        $author = User::factory()->create();
        $replier = User::factory()->create();
        $nestedAuthor = User::factory()->create();
        $authorHandle = 'parent_comment_handle';
        $replierHandle = 'first_reply_handle';

        foreach ([
            [$author->id, 'Parent', $authorHandle],
            [$replier->id, 'Replier', $replierHandle],
            [$nestedAuthor->id, 'Nested', 'nested_user_handle'],
        ] as [$userId, $displayName, $handle]) {
            DB::table('user_profiles')->insert([
                'user_id' => $userId,
                'display_name' => $displayName,
                'handle' => $handle,
                'is_private' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $postId = $this->insertRegularPost($author->id);
        $commentId = $this->insertComment($postId, 'regular', $author->id, 'Parent');

        $this->actingAs($replier, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/responses", [
                'post_type' => 'regular',
                'response' => 'Première réponse',
                'is_reponse_to_main_comment' => true,
            ])
            ->assertAccepted();

        $this->actingAs($nestedAuthor, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/responses", [
                'post_type' => 'regular',
                'response' => "@{$replierHandle} merci",
                'responded_to_who' => $replierHandle,
                'is_reponse_to_main_comment' => false,
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('response_commentaires', [
            'comment_id' => $commentId,
            'users_id' => $nestedAuthor->id,
            'is_reponse_to_main_comment' => 0,
            'responded_to_who' => $replierHandle,
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'total_comments' => 2,
        ]);
    }

    public function test_comment_response_increments_publication_total_comments_in_job(): void
    {
        $author = User::factory()->create();
        $replier = User::factory()->create();
        $authorHandle = 'reply_target_handle';
        DB::table('user_profiles')->insert([
            'user_id' => $author->id,
            'display_name' => 'Jules Demo',
            'handle' => $authorHandle,
            'is_private' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $postId = $this->insertRegularPost($author->id);
        $commentId = $this->insertComment($postId, 'regular', $author->id, 'Parent');

        $this->actingAs($replier, 'sanctum')
            ->postJson("/api/v1/auth/posts/{$postId}/comments/{$commentId}/responses", [
                'post_type' => 'regular',
                'response' => "@{$authorHandle} cool",
                'responded_to_who' => $authorHandle,
                'is_reponse_to_main_comment' => true,
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('response_commentaires', [
            'comment_id' => $commentId,
            'users_id' => $replier->id,
            'response' => "@{$authorHandle} cool",
        ]);
        $this->assertDatabaseHas('comments', [
            'id' => $commentId,
            'responses_count' => 1,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'total_comments' => 1,
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
