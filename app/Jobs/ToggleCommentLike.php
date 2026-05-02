<?php

namespace App\Jobs;

use App\Services\Post\CommentLikeService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ToggleCommentLike implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $publicationId,
        private readonly int $commentId,
        private readonly int $userId,
        private readonly string $publicationType,
        private readonly string $action,
    ) {}

    public function handle(CommentLikeService $service): void
    {
        $service->toggleLike(
            $this->publicationId,
            $this->commentId,
            $this->userId,
            $this->publicationType,
            $this->action,
        );
    }

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function retryUntil(): DateTime
    {
        return (new DateTime)->add(new \DateInterval('PT24H'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->publicationId.'-'.$this->commentId.'-'.$this->userId.'-'.$this->action),
        ];
    }
}
