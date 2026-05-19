<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\FollowNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FollowNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $kind,
        public readonly int $actorUserId,
        public readonly int $recipientUserId,
        public readonly ?int $followId = null,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(ExpoPushService $expoPushService): void
    {
        if ($this->actorUserId === $this->recipientUserId) {
            return;
        }

        $followQuery = DB::table('follows');

        if ($this->followId !== null) {
            $followQuery->where('id', $this->followId);
        }

        $follow = $followQuery
            ->when($this->followId === null, function ($query): void {
                $query->where('follower_id', $this->actorUserId);
            })
            ->first();

        if ($follow === null) {
            return;
        }

        $followerId = (int) $follow->follower_id;
        $followingId = (int) $follow->following_id;
        $status = (string) $follow->status;

        $valid = match ($this->kind) {
            'new_follower' => $status === 'accepted'
                && $followerId === $this->actorUserId
                && $followingId === $this->recipientUserId,
            'follow_request' => $status === 'pending'
                && $followerId === $this->actorUserId
                && $followingId === $this->recipientUserId,
            'follow_accepted' => $status === 'accepted'
                && $followingId === $this->actorUserId
                && $followerId === $this->recipientUserId,
            default => false,
        };

        if (! $valid) {
            return;
        }

        $recipient = User::query()->find($this->recipientUserId);
        if ($recipient === null) {
            return;
        }

        $actorName = User::query()->whereKey($this->actorUserId)->value('name');
        $message = $this->messageForKind($actorName);
        $title = $actorName ?? 'Un utilisateur';

        Notification::send($recipient, new FollowNotification(
            $this->kind,
            $this->actorUserId,
            $this->followId ?? (int) $follow->id,
            $message,
        ));

        $rawToken = $recipient->fcm_token;
        if (! is_string($rawToken) || $rawToken === '') {
            logger()->warning('FollowNotificationJob: destinataire sans fcm_token.', [
                'recipient_id' => $this->recipientUserId,
            ]);

            return;
        }

        if (! str_starts_with($rawToken, 'ExponentPushToken[')) {
            logger()->warning('FollowNotificationJob: fcm_token n\'est pas un jeton Expo (probable FCM natif en base).', [
                'recipient_id' => $this->recipientUserId,
            ]);

            return;
        }

        $expoTokens = [$rawToken];

        $data = [
            'kind' => $this->kind,
            'actor_user_id' => $this->actorUserId,
            'follow_id' => $this->followId ?? (int) $follow->id,
            'message' => $message,
        ];

        $expoPushService->send(
            $expoTokens,
            $title,
            $message,
            null,
            $data,
        );
    }

    private function messageForKind(?string $actorName): string
    {
        $name = $actorName ?? 'Un utilisateur';

        return match ($this->kind) {
            'new_follower' => $name.' vous suit',
            'follow_request' => $name.' souhaite vous suivre',
            'follow_accepted' => $name.' a accepté votre demande de suivi',
            default => $name,
        };
    }
}
