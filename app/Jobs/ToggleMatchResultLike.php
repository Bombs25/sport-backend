<?php

namespace App\Jobs;

use App\Services\Post\MatchResultLikeService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ToggleMatchResultLike implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $publicationId,
        private readonly int $userId,
        private readonly string $publicationType,
        private readonly string $action,
    ) {}

    public function handle(MatchResultLikeService $service): void
    {
        $service->toggleLike(
            $this->publicationId,
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

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->publicationId.'-'.$this->publicationType.'-'.$this->userId),
        ];
    }
}
