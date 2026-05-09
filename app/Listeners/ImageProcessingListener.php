<?php

namespace App\Listeners;

use App\Contracts\Files\ImageProcessingInterface;
use App\Events\ImageProcessingEvent;
use App\Http\Requests\Api\V1\Images\ImageProcessingStoreRequest;
use App\Jobs\CompressImageJob;
use App\Jobs\ConvertImageJob;
use App\Jobs\GenerateBlurHashJob;
use App\Jobs\ImageProcessingQueue;
use App\Support\ImagePipelineResultCache;
use Illuminate\Bus\Batch;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageProcessingListener
{
    public function __construct(
        private readonly ImageProcessingInterface $imageProcessing,
    ) {}

    /**
     * Valide les fichiers comme {@see ImageProcessingStoreRequest}, stocke chaque image unique (hash contenu),
     * puis lance un lot ({@see Bus::batch}) : blur seul, et [compress → convert] en chaîne au sein du même lot.
     */
    public function handle(ImageProcessingEvent $event): void
    {
        $files = ImageProcessingStoreRequest::validatedFileList($event->files);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk();
        $batchKey = $event->uniqueKey;

        $seenContentHashes = [];
        $storedPaths = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->getRealPath() ?: $file->getPathname();
            if ($path === '' || ! is_readable($path)) {
                continue;
            }

            $contentHash = hash_file('sha256', $path);
            if (isset($seenContentHashes[$contentHash])) {
                continue;
            }
            $seenContentHashes[$contentHash] = true;

            $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin'));
            $filename = "{$contentHash}.{$extension}";
            $relativePath = "{$batchKey}/{$filename}";

            if (! $disk->exists($relativePath)) {
                $contents = file_get_contents($path);
                if ($contents === false) {
                    continue;
                }
                $disk->put($relativePath, $contents);
            }

            $storedPaths[] = $relativePath;
        }

        if ($storedPaths === []) {
            return;
        }

        $user = $event->user;
        $userId = (int) $user->id;
        $variant = $event->variant;
        $paths = $storedPaths;
        $processing = $this->imageProcessing;

        Bus::batch([
            new GenerateBlurHashJob($user, $batchKey, $processing, $paths),
            [
                new CompressImageJob($user, $batchKey, $processing, $paths, $event->variant),
                new ConvertImageJob($user, $batchKey, $processing, $event->variant),
            ],
        ])
            ->before(function (Batch $batch) {
                // The batch has been created but no jobs have been added...
            })->progress(function (Batch $batch) use ($batchKey, $userId) {
                $payload = self::batchProgressPayload($batch);

                Cache::put(
                    ImagePipelineResultCache::progressKey($batchKey, $userId),
                    $payload,
                    now()->addMinutes(30),
                );

                Log::info('Image processing batch progress.', [
                    'batch_id' => $batch->id,
                    'name' => $batch->name,
                    'progress_bar' => $payload['progress_bar'],
                    'percent' => $payload['percent'],
                    'processed_jobs' => $payload['processed_jobs'],
                    'total_jobs' => $payload['total_jobs'],
                ]);
            })->then(function (Batch $batch) {
                // Log::info('Image processing batch completed successfully.', [
                //     'batch_id' => $batch->id,
                //     'name' => $batch->name,
                //     'total_jobs' => $batch->totalJobs,
                // ]);
            })->catch(function (Batch $batch, Throwable $e) {
                Log::error('Image processing batch failed.', [
                    'batch_id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'error' => $e->getMessage(),
                ]);
            })->finally(function (Batch $batch) use ($batchKey, $userId, $variant) {
                $defaultDisk = Storage::disk();
                $suffix = (string) $variant->value;

                if ($defaultDisk->exists($batchKey)) {
                    $defaultDisk->deleteDirectory($batchKey);
                }

                Cache::forget("image-pipeline:compressed:{$batchKey}:{$userId}:{$suffix}");

                $blurKey = ImagePipelineResultCache::blurhashKey($batchKey, $userId);
                $convertKey = ImagePipelineResultCache::convertKey($batchKey, $userId, $variant);

                $blurhash = Cache::get($blurKey);
                $convertJson = Cache::get($convertKey);
                $convertPaths = is_string($convertJson)
                    ? json_decode($convertJson, true)
                    : null;

                Log::info('Image processing batch finished.', [
                    'batch_id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'blurhash' => $blurhash,
                    'convert_paths' => is_array($convertPaths) ? $convertPaths : $convertJson,
                ]);

                Cache::forget(ImagePipelineResultCache::progressKey($batchKey, $userId));
                Cache::forget($blurKey);
                Cache::forget($convertKey);
            })
            ->name("image-processing:{$batchKey}")
            ->onQueue(ImageProcessingQueue::NAME)
            ->dispatch();
    }

    /**
     * @return array{
     *     batch_id: string,
     *     percent: int,
     *     processed_jobs: int,
     *     total_jobs: int,
     *     pending_jobs: int,
     *     failed_jobs: int,
     *     progress_bar: string
     * }
     */
    private static function batchProgressPayload(Batch $batch): array
    {
        $total = max(1, $batch->totalJobs);
        $processed = $batch->totalJobs - $batch->pendingJobs;
        $percent = (int) min(100, max(0, (int) floor((100 * $processed) / $total)));

        $width = 24;
        $filled = (int) round($width * $percent / 100);
        $filled = min($width, max(0, $filled));
        $bar = '['.str_repeat('█', $filled).str_repeat('░', $width - $filled)."] {$percent}%";

        return [
            'batch_id' => $batch->id,
            'percent' => $percent,
            'processed_jobs' => $processed,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'progress_bar' => $bar,
        ];
    }
}
