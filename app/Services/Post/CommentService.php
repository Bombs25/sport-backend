<?php

namespace App\Services\Post;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentService
{
    public function createComment(int $publicationId, int $userId, string $publicationType, string $content): int
    {
        return (int) DB::table('comments')->insertGetId([
            'publication_id' => $publicationId,
            'publication_type' => $publicationType,
            'user_id' => $userId,
            'content' => $content,
            'created_at' => now(),
        ]);
    }

    /**
     * Crée une réponse à un commentaire et met à jour les compteurs de manière atomique.
     *
     * @throws ValidationException
     */
    public function createCommentResponse(
        int $publicationId,
        int $commentId,
        int $userId,
        string $publicationType,
        string $response,
        ?string $respondedToWho = null,
        bool $isReponseToMainComment = true,
    ): int {
        return DB::transaction(function () use (
            $publicationId,
            $commentId,
            $userId,
            $publicationType,
            $response,
            $respondedToWho,
            $isReponseToMainComment
        ): int {
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

            $responseId = (int) DB::table('response_commentaires')->insertGetId([
                'comment_id' => $commentId,
                'is_reponse_to_main_comment' => $isReponseToMainComment,
                'response' => $response,
                'responded_to_who' => $respondedToWho,
                'users_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('comments')
                ->where('id', $commentId)
                ->increment('responses_count');

            DB::table('match_results')
                ->where('id', $publicationId)
                ->increment('total_comments');

            return $responseId;
        });
    }

    /**
     * Vérifie rapidement les préconditions avant d'envoyer le job.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assertCanDeleteComment(int $commentId, int $publicationId, string $publicationType, int $actorUserId): void
    {
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

        if ((int) $comment->user_id !== $actorUserId) {
            throw new AuthorizationException(__('Vous ne pouvez supprimer que vos propres commentaires.'));
        }
    }

    /**
     * Supprime un commentaire et met à jour les compteurs dans une transaction ACID.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function deleteComment(int $commentId, int $publicationId, string $publicationType, int $actorUserId): void
    {
        DB::transaction(function () use ($commentId, $publicationId, $publicationType, $actorUserId): void {
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

            if ((int) $comment->user_id !== $actorUserId) {
                throw new AuthorizationException(__('Vous ne pouvez supprimer que vos propres commentaires.'));
            }

            DB::table('comments')
                ->where('id', $commentId)
                ->delete();

            if ((string) $comment->publication_type === 'automatic') {
                DB::table('match_results')
                    ->where('id', (int) $comment->publication_id)
                    ->where('total_comments', '>', 0)
                    ->decrement('total_comments');
            }
        });
    }
}
