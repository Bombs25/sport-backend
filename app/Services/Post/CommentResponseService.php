<?php

namespace App\Services\Post;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentResponseService
{
    /**
     * Vérifie rapidement que l'utilisateur peut supprimer cette réponse.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assertCanDeleteCommentResponse(
        int $responseId,
        int $commentId,
        int $publicationId,
        string $publicationType,
        int $actorUserId,
    ): void {
        $comment = DB::table('comments')
            ->where('id', $commentId)
            ->where('publication_id', $publicationId)
            ->where('publication_type', $publicationType)
            ->first();

        if ($comment === null) {
            throw ValidationException::withMessages([
                'comment_id' => __('Commentaire introuvable pour cette publication.'),
            ]);
        }

        $response = DB::table('response_commentaires')
            ->where('id', $responseId)
            ->where('comment_id', $commentId)
            ->first();

        if ($response === null) {
            throw ValidationException::withMessages([
                'response_id' => __('Réponse introuvable pour ce commentaire.'),
            ]);
        }

        if ((int) $response->users_id !== $actorUserId) {
            throw new AuthorizationException(__('Vous ne pouvez supprimer que vos propres réponses.'));
        }
    }

    /**
     * Supprime une réponse et met à jour les compteurs de manière ACID.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function deleteCommentResponse(
        int $responseId,
        int $commentId,
        int $publicationId,
        string $publicationType,
        int $actorUserId,
    ): void {
        DB::transaction(function () use ($responseId, $commentId, $publicationId, $publicationType, $actorUserId): void {
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

            if ((int) $response->users_id !== $actorUserId) {
                throw new AuthorizationException(__('Vous ne pouvez supprimer que vos propres réponses.'));
            }

            DB::table('response_commentaires')
                ->where('id', $responseId)
                ->delete();

            DB::table('comments')
                ->where('id', $commentId)
                ->where('responses_count', '>', 0)
                ->decrement('responses_count');

            if ((string) $comment->publication_type === 'automatic') {
                DB::table('match_results')
                    ->where('id', $publicationId)
                    ->where('total_comments', '>', 0)
                    ->decrement('total_comments');
            }
        });
    }
}
