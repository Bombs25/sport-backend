<?php

namespace App\Jobs;

use App\Services\Post\CommentService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DeleteComment implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $commentId,
        private readonly int $publicationId,
        private readonly string $publicationType,
        private readonly int $actorUserId,
    ) {}

    public function handle(CommentService $service): void
    {
        $service->deleteComment(
            $this->commentId,
            $this->publicationId,
            $this->publicationType,
            $this->actorUserId,
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
            new WithoutOverlapping($this->commentId.'-'.$this->publicationId.'-'.$this->actorUserId),
        ];
    }
}
