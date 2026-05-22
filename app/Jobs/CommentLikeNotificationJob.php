<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\CommentLikeNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CommentLikeNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private readonly int $publicationId,
        private readonly int $commentId,
        private readonly int $actorUserId,
        private readonly string $publicationType,
        private readonly string $action,
    ) {}

    /**
     * The number of times the queued notification may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the notification.
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(ExpoPushService $expoPushService): void
    {

        if ($this->action !== 'like') {
            return;
        }
        logger()->debug('CommentLikeNotificationJob started');
        $likeExists = DB::table('comments_likes')
            ->where('users_id', $this->actorUserId)
            ->where('comment_id', $this->commentId)
            ->exists();

        if (! $likeExists) {
            return;
        }

        $comment = DB::table('comments')
            ->where('id', $this->commentId)
            ->where('publication_id', $this->publicationId)
            ->where('publication_type', $this->publicationType)
            ->select(['user_id'])
            ->first();

        // Pas de notification si l'auteur like son propre commentaire (le like en base est autorisé).
        if ($comment === null || (int) $comment->user_id === $this->actorUserId) {
            logger()->debug('CommentLikeNotificationJob ended 1');

            return;
        }

        $recipient = User::query()->find((int) $comment->user_id);
        // Stop when the comment owner account cannot be resolved.
        if ($recipient === null) {
            logger()->debug('CommentLikeNotificationJob ended');

            return;
        }

        $actorName = User::query()->whereKey($this->actorUserId)->value('name');
        $message = ($actorName ?? 'Un utilisateur').' a aime votre commentaire';
        $title = $actorName ?? 'Un utilisateur';

        Notification::send($recipient, new CommentLikeNotification(
            $this->publicationId,
            $this->commentId,
            $this->actorUserId,
            $this->publicationType,
            $actorName,
        ));

        $expoTokens = User::expoPushTokensFrom([$recipient]);

        if ($expoTokens === []) {
            return;
        }

        $data = [
            'notif_type' => NotificationType::LIKE_COMMENT,
            'publication_id' => $this->publicationId,
            'comment_id' => $this->commentId,
            'publication_type' => $this->publicationType,
            'user_id' => $this->actorUserId,
            'message' => $message,
        ];

        logger()->debug('Expo tokens for push notification', ['tokens' => $expoTokens]);
        $expoPushService->send(
            $expoTokens,
            $title,
            $message,
            null,
            json_encode($data) ?? null,
        );
    }
}
