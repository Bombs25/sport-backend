<?php

namespace App\Jobs;

use App\Services\Post\CommentService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AddComment implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private readonly int $publicationId,
        private readonly int $userId,
        private readonly string $publicationType,
        private readonly string $content,
    ) {}

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    public function handle(CommentService $service): int
    {
        return DB::transaction(function () use ($service): int {
            $commentId = $service->createComment(
                $this->publicationId,
                $this->userId,
                $this->publicationType,
                $this->content,
            );

            DB::table($this->publicationType === 'regular' ? 'posts' : 'match_results')
                ->where('id', $this->publicationId)
                ->increment('total_comments');

            return $commentId;
        });
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 20, 30];
    }

    /**
     * The job will be retried for 24 hours.
     */
    public function retryUntil(): DateTime
    {
        return (new DateTime)->add(new \DateInterval('PT24H'));
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->publicationId.'-'.$this->userId.'-'.$this->publicationType),
        ];
    }
}
