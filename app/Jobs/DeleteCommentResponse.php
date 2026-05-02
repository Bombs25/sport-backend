<?php

namespace App\Jobs;

use App\Services\Post\CommentResponseService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DeleteCommentResponse implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $responseId,
        private readonly int $commentId,
        private readonly int $publicationId,
        private readonly string $publicationType,
        private readonly int $actorUserId,
    ) {}

    public function handle(CommentResponseService $service): void
    {
        $service->deleteCommentResponse(
            $this->responseId,
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
            new WithoutOverlapping($this->responseId.'-'.$this->commentId.'-'.$this->publicationId.'-'.$this->actorUserId),
        ];
    }
}
