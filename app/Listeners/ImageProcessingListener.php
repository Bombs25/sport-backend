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

            if (! $disk->exists($filename)) {
                $contents = file_get_contents($path);
                if ($contents === false) {
                    continue;
                }
                $disk->put($filename, $contents);
            }

            $storedPaths[] = $filename;
        }

        if ($storedPaths === []) {
            return;
        }

        $batchKey = $event->uniqueKey;
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
            })->progress(function (Batch $batch) {
                // A single job has completed successfully...
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

                Cache::forget($blurKey);
                Cache::forget($convertKey);
            })
            ->name("image-processing:{$batchKey}")
            ->onQueue(ImageProcessingQueue::NAME)
            ->dispatch();
    }
}
