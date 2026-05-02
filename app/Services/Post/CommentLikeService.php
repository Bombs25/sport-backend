<?php

namespace App\Services\Post;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentLikeService
{
    /**
     * Applique un like/dislike de commentaire de manière transactionnelle (ACID).
     *
     * @return array{liked: bool, changed: bool, likes_count: int, comment_owner_id: int}
     *
     * @throws ValidationException
     */
    public function toggleLike(
        int $publicationId,
        int $commentId,
        int $userId,
        string $publicationType,
        string $action,
    ): array {
        return DB::transaction(function () use ($publicationId, $commentId, $userId, $publicationType, $action): array {
            $comment = DB::table('comments')
                ->where('id', $commentId)
                ->where('publication_id', $publicationId)
                ->where('publication_type', $publicationType)
                ->lockForUpdate()
                ->first();

            if ($comment === null) {
                throw ValidationException::withMessages([
                    'comment_id' => __('Commentaire introuvable pour cette publication.'),
                ]);
            }

            $existingLike = DB::table('comments_likes')
                ->where('users_id', $userId)
                ->where('comment_id', $commentId)
                ->lockForUpdate()
                ->first();

            $likesCount = (int) $comment->likes_count;
            $liked = $existingLike !== null;
            $changed = false;

            if ($action === 'like') {
                if ($existingLike === null) {
                    DB::table('comments_likes')->insert([
                        'users_id' => $userId,
                        'comment_id' => $commentId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('comments')
                        ->where('id', $commentId)
                        ->increment('likes_count');

                    $likesCount++;
                    $liked = true;
                    $changed = true;
                }
            } else {
                if ($existingLike !== null) {
                    DB::table('comments_likes')
                        ->where('users_id', $userId)
                        ->where('comment_id', $commentId)
                        ->delete();

                    DB::table('comments')
                        ->where('id', $commentId)
                        ->where('likes_count', '>', 0)
                        ->decrement('likes_count');

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
                'comment_owner_id' => (int) $comment->user_id,
            ];
        });
    }
}
