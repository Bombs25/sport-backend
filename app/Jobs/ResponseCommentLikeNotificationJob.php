<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ResponseCommentLikeNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ResponseCommentLikeNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private readonly int $publicationId,
        private readonly int $commentId,
        private readonly int $responseId,
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

        $likeExists = DB::table('response_comment_like')
            ->where('user_id', $this->actorUserId)
            ->where('responses_comment_id', $this->responseId)
            ->exists();

        if (! $likeExists) {
            return;
        }

        $response = DB::table('response_commentaires')
            ->where('id', $this->responseId)
            ->where('comment_id', $this->commentId)
            ->select(['users_id'])
            ->first();

        if ($response === null || (int) $response->users_id === $this->actorUserId) {
            return;
        }

        $recipient = User::query()->find((int) $response->users_id);
        if ($recipient === null) {
            return;
        }

        $actorName = User::query()->whereKey($this->actorUserId)->value('name');
        $message = ($actorName ?? 'Un utilisateur').' a aime votre reponse';
        $title = $actorName ?? 'Un utilisateur';

        Notification::send($recipient, new ResponseCommentLikeNotification(
            $this->publicationId,
            $this->commentId,
            $this->responseId,
            $this->actorUserId,
            $this->publicationType,
            $actorName,
        ));

        $expoTokens = collect([$recipient])
            ->map(static fn (User $user) => $user->routeNotificationForFcm())
            ->flatten()
            ->filter(static fn ($token): bool => is_string($token) && $token !== '')
            ->values()
            ->all();

        $data = [
            'publication_id' => $this->publicationId,
            'comment_id' => $this->commentId,
            'response_id' => $this->responseId,
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
