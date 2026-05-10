<?php

namespace App\Jobs;

use App\Contracts\Files\ImageProcessingInterface;
use App\Enums\ImageVariantLongEdge;
use App\Models\User;
use App\Support\ImagePipelineResultCache;
use DateTimeInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class CompressImageJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    public function __construct(
        public User $user,
        public string $uniqueKey,
        public ImageProcessingInterface $imageProcessing,
        public array $paths,
        public ImageVariantLongEdge $variant,
    ) {
        $this->onQueue(ImageProcessingQueue::NAME);
    }

    public function uniqueId(): string
    {
        $base = "{$this->uniqueKey}-{$this->user->id}-{$this->variant->value}";

        return $this->batchId ? "{$base}-{$this->batchId}" : $base;
    }

    public function uniqueFor(): int
    {
        return 60 * 10;
    }

    public function tries(): int
    {
        return 5;
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public int $maxExceptions = 3;

    public int $timeout = 120;

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $compressedPaths = $this->imageProcessing->compress($this->paths, $this->variant);

        Cache::put(
            $this->compressedPathsCacheKey(),
            $compressedPaths,
            now()->addMinutes(30),
        );
    }

    private function compressedPathsCacheKey(): string
    {
        return ImagePipelineResultCache::compressedPathsKey(
            $this->uniqueKey,
            (int) $this->user->id,
            $this->variant,
            (string) ($this->batchId ?? ''),
        );
    }
}
