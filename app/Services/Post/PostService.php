<?php

namespace App\Services\Post;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    /**
     * Soft-delete un post régulier après avoir vérifié que l'utilisateur en
     * est l'auteur. Couvre uniquement la table `posts` (les "posts" de score
     * validé vivent dans `match_results`, hors de cet endpoint).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function deleteRegularPost(int $postId, int $actorUserId): void
    {
        DB::transaction(function () use ($postId, $actorUserId): void {
            $post = DB::table('posts')
                ->where('id', $postId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if ($post === null) {
                throw ValidationException::withMessages([
                    'post_id' => __('Post introuvable.'),
                ]);
            }

            if ((int) $post->user_id !== $actorUserId) {
                throw new AuthorizationException(__('Vous ne pouvez supprimer que vos propres posts.'));
            }

            DB::table('posts')
                ->where('id', $postId)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    }
}
