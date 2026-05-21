<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Comments;
use App\Services\Notifications\ExpoPushService;
use App\Support\PostPublicationNotificationMessages;
use App\Support\PostPublicationNotificationRecipients;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class CommentJobNotification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function __construct(
        private readonly int $publicationId,
        private readonly int $userId,
        private readonly string $publicationType,
        private readonly string $content,
        private readonly bool $isResponse = false,
    ) {}

    public function handle(ExpoPushService $expoPushService): void
    {
        $senderName = User::query()
            ->whereKey($this->userId)
            ->value('name');

        $logContext = self::class.'#'.$this->publicationId;
        $users = PostPublicationNotificationRecipients::usersFor(
            $this->publicationId,
            $this->publicationType,
            $this->userId,
        );

        if ($users->isEmpty()) {
            return;
        }

        $message = PostPublicationNotificationMessages::commentMessage(
            $this->publicationType,
            $senderName,
            $this->isResponse,
        );

        Notification::send($users, new Comments(
            $this->publicationId,
            $this->userId,
            $this->publicationType,
            $this->content,
            $senderName,
            $this->isResponse,
        ));

        $expoTokens = User::expoPushTokensFrom($users, $logContext);

        if ($expoTokens === []) {
            return;
        }

        $data = [
            'publication_id' => $this->publicationId,
            'publication_type' => $this->publicationType,
            'content' => $this->content,
            'user_id' => $this->userId,
            'is_response' => $this->isResponse,
            'message' => $message,
        ];

        $expoPushService->send(
            $expoTokens,
            $message,
            $this->content,
            null,
            json_encode($data) ?? null,
        );
    }
}
