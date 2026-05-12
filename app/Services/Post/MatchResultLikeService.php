<?php

namespace App\Services\Post;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchResultLikeService
{
    /**
     * Applique un like/dislike (retrait du like) sur une publication de manière transactionnelle (ACID).
     *
     * @return array{liked: bool, changed: bool, likes_count: int, submitted_by_user_id: int}
     *
     * @throws ValidationException
     */
    public function toggleLike(
        int $publicationId,
        int $userId,
        string $publicationType,
        string $action,
    ): array {
        return DB::transaction(function () use ($publicationId, $userId, $publicationType, $action): array {
            $publicationTable = $publicationType === 'regular' ? 'posts' : 'match_results';
            $publication = DB::table($publicationTable)
                ->where('id', $publicationId)
                ->lockForUpdate()
                ->first();

            if ($publication === null) {
                throw ValidationException::withMessages([
                    'post_id' => 'Publication introuvable.',
                ]);
            }

            $existingLike = DB::table('post_likes')
                ->where('users_id', $userId)
                ->where('publication_id', $publicationId)
                ->where('publication_type', $publicationType)
                ->lockForUpdate()
                ->first();

            $likesCount = (int) $publication->total_likes;
            $liked = $existingLike !== null;
            $changed = false;

            if ($action === 'like') {
                if ($existingLike === null) {
                    DB::table('post_likes')->insert([
                        'users_id' => $userId,
                        'publication_id' => $publicationId,
                        'publication_type' => $publicationType,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table($publicationTable)
                        ->where('id', $publicationId)
                        ->increment('total_likes');

                    $likesCount++;
                    $liked = true;
                    $changed = true;
                }
            } else {
                if ($existingLike !== null) {
                    DB::table('post_likes')
                        ->where('users_id', $userId)
                        ->where('publication_id', $publicationId)
                        ->where('publication_type', $publicationType)
                        ->delete();

                    DB::table($publicationTable)
                        ->where('id', $publicationId)
                        ->where('total_likes', '>', 0)
                        ->decrement('total_likes');

                    $likesCount = max(0, $likesCount - 1);
                    $liked = false;
                    $changed = true;
                } else {
                    $liked = false;
                }
            }

            return [
                'liked' => $liked,
                'changed' => $changed,
                'likes_count' => $likesCount,
                'submitted_by_user_id' => $publicationType === 'regular'
                    ? (int) $publication->user_id
                    : (int) $publication->submitted_by_user_id,
            ];
        });
    }
}
