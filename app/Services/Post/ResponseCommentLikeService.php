<?php

namespace App\Services\Post;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResponseCommentLikeService
{
    /**
     * @return array{liked: bool, changed: bool, likes_count: int, response_owner_id: int}
     *
     * @throws ValidationException
     */
    public function toggleLike(
        int $publicationId,
        int $commentId,
        int $responseId,
        int $userId,
        string $publicationType,
        string $action,
    ): array {
        return DB::transaction(function () use ($publicationId, $commentId, $responseId, $userId, $publicationType, $action): array {
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

            $response = DB::table('response_commentaires')
                ->where('id', $responseId)
                ->where('comment_id', $commentId)
                ->lockForUpdate()
                ->first();

            if ($response === null) {
                throw ValidationException::withMessages([
                    'response_id' => __('Réponse introuvable pour ce commentaire.'),
                ]);
            }

            $existingLike = DB::table('response_comment_like')
                ->where('user_id', $userId)
                ->where('responses_comment_id', $responseId)
                ->lockForUpdate()
                ->first();

            $likesCount = (int) $response->likes_count;
            $liked = $existingLike !== null;
            $changed = false;

            if ($action === 'like') {
                if ($existingLike === null) {
                    DB::table('response_comment_like')->insert([
                        'user_id' => $userId,
                        'responses_comment_id' => $responseId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('response_commentaires')
                        ->where('id', $responseId)
                        ->increment('likes_count');

                    $likesCount++;
                    $liked = true;
                    $changed = true;
                }
            } else {
                if ($existingLike !== null) {
                    DB::table('response_comment_like')
                        ->where('user_id', $userId)
                        ->where('responses_comment_id', $responseId)
                        ->delete();

                    DB::table('response_commentaires')
                        ->where('id', $responseId)
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
                'response_owner_id' => (int) $response->users_id,
            ];
        });
    }
}
