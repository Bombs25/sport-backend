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
use RuntimeException;

class ConvertImageJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    public function __construct(
        public User $user,
        public string $uniqueKey,
        public ImageProcessingInterface $imageProcessing,
        public ImageVariantLongEdge $variant,
    ) {
        $this->onQueue(ImageProcessingQueue::NAME);
    }

    public function uniqueId(): string
    {
        return "{$this->uniqueKey}-{$this->user->id}-{$this->variant->value}";
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

        $paths = Cache::get($this->compressedPathsCacheKey());
        if (! is_array($paths) || $paths === []) {
            throw new RuntimeException('Compressed paths missing for convert step.');
        }

        $convertJson = $this->imageProcessing->convert($paths);

        Cache::put(
            ImagePipelineResultCache::convertKey($this->uniqueKey, (int) $this->user->id, $this->variant),
            $convertJson,
            ImagePipelineResultCache::ttl(),
        );

        Cache::forget($this->compressedPathsCacheKey());
    }

    private function compressedPathsCacheKey(): string
    {
        return "image-pipeline:compressed:{$this->uniqueKey}:{$this->user->id}:{$this->variant->value}";
    }
}
