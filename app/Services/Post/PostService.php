<?php

namespace App\Services\Post;

use Illuminate\Support\Facades\DB;

class PostService
{
    /**
     * @return array<string, mixed>
     */
    public function createRegularPost(
        int $userId,
        ?string $body,
        string $visibility,
        int $mediaCount,
    ): array {
        $normalizedBody = is_string($body) && trim($body) !== '' ? trim($body) : null;
        $now = now();

        return DB::transaction(function () use ($userId, $normalizedBody, $visibility, $mediaCount, $now): array {
            $postId = (int) DB::table('posts')->insertGetId([
                'user_id' => $userId,
                'body' => $normalizedBody,
                'visibility' => $visibility,
                'status' => 'published',
                'media_count' => $mediaCount,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_shares' => 0,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'id' => $postId,
                'user_id' => $userId,
                'body' => $normalizedBody,
                'visibility' => $visibility,
                'status' => 'published',
                'media_count' => $mediaCount,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_shares' => 0,
                'published_at' => $now->toJSON(),
                'media' => [],
            ];
        });
    }
}
