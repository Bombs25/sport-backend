<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\MatchResultLikeNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\PostPublicationNotificationMessages;
use App\Support\PostPublicationNotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MatchResultLikeNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private readonly int $matchResultId,
        private readonly int $actorUserId,
        private readonly string $publicationType,
        private readonly string $action,
    ) {}

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(ExpoPushService $expoPushService): void
    {
        if ($this->action !== 'like') {
            return;
        }

        $likeExists = DB::table('post_likes')
            ->where('users_id', $this->actorUserId)
            ->where('publication_id', $this->matchResultId)
            ->where('publication_type', $this->publicationType)
            ->exists();

        if (! $likeExists) {
            return;
        }

        $logContext = self::class.'#'.$this->matchResultId;
        $users = PostPublicationNotificationRecipients::usersFor(
            $this->matchResultId,
            $this->publicationType,
            $this->actorUserId,
        );

        logger()->debug('MatchResultLikeNotificationJob: destinataires', [
            'publication_id' => $this->matchResultId,
            'publication_type' => $this->publicationType,
            'actor_user_id' => $this->actorUserId,
            'recipient_user_ids' => $users->pluck('id')->all(),
            'fcm_token_db_par_user' => $users->mapWithKeys(static fn (User $user): array => [
                $user->id => User::formatTokenForLog($user->fcm_token),
            ])->all(),
        ]);

        if ($users->isEmpty()) {
            return;
        }

        $actorName = User::query()->whereKey($this->actorUserId)->value('name');
        $message = PostPublicationNotificationMessages::likeMessage($this->publicationType, $actorName);
        $title = $actorName ?? 'Un utilisateur';

        Notification::send($users, new MatchResultLikeNotification(
            $this->matchResultId,
            $this->actorUserId,
            $this->publicationType,
            $actorName,
        ));

        $expoTokens = User::expoPushTokensFrom($users, $logContext);

        if ($expoTokens === []) {
            logger()->warning('MatchResultLikeNotificationJob: aucun jeton Expo exploitable', [
                'publication_id' => $this->matchResultId,
                'publication_type' => $this->publicationType,
                'recipient_user_ids' => $users->pluck('id')->all(),
            ]);

            return;
        }

        $data = [
            'publication_id' => $this->matchResultId,
            'publication_type' => $this->publicationType,
            'user_id' => $this->actorUserId,
            'message' => $message,
        ];

        $expoPushService->send(
            $expoTokens,
            $title,
            $message,
            null,
            json_encode($data) ?? null,
        );
    }
}
