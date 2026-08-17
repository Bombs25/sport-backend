<?php

namespace App\Listeners;

use App\Contracts\Files\ImageProcessingInterface;
use App\Events\FileUploadBroadcast;
use App\Events\ImageProcessingEvent;
use App\Http\Requests\Api\V1\Images\ImageProcessingStoreRequest;
use App\Jobs\CompressImageJob;
use App\Jobs\ConvertImageJob;
use App\Jobs\GenerateBlurHashJob;
use App\Jobs\ImageProcessingQueue;
use App\Models\User;
use App\Repositories\AddFilesRepository;
use App\Support\ImagePipelineResultCache;
use Illuminate\Bus\Batch;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageProcessingListener
{
    public function __construct(
        private readonly ImageProcessingInterface $imageProcessing,
        private readonly AddFilesRepository $addFilesRepository,
    ) {}

    /**
     * Pipeline d’upload image côté backend (toute ressource : équipe, endpoint générique {@code /images}, etc.) :
     * validation comme {@see ImageProcessingStoreRequest}, stockage par hash de contenu sous le disque staging,
     * puis lot {@see Bus::batch} (blur ; chaîne compress → convert). {@see ImageProcessingEvent::$uniqueKey} est
     * choisi par l’appelant pour isoler fichiers temporaires et caches.
     */
    public function handle(ImageProcessingEvent $event): void
    {
        $files = ImageProcessingStoreRequest::validatedFileList($event->files);

        /** @var FilesystemAdapter $disk staging privé ({@see ImageProcessingInterface::STAGING_DISK}) */
        $disk = Storage::disk(ImageProcessingInterface::STAGING_DISK);
        $batchKey = $event->uniqueKey;
        $variant = $event->variant;

        $seenContentHashes = [];
        /** @var list<array{0: UploadedFile, 1: string, 2: string}> $candidates file, contentHash, filename */
        $candidates = [];

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
            $candidates[] = [$file, $contentHash, $filename];
        }

        if ($candidates === []) {
            return;
        }

        $fpHashes = array_column($candidates, 1);
        sort($fpHashes, SORT_STRING);
        $fingerprint = hash('sha256', $variant->value . "\0" . implode("\0", $fpHashes));
        $dedupKey = "image-processing:dedup:{$batchKey}:{$fingerprint}";

        if (! Cache::add($dedupKey, true, now()->addMinutes(30))) {
            Log::info('Image processing skipped (duplicate dispatch).', [
                'batch_key' => $batchKey,
            ]);

            return;
        }

        $storedPaths = [];
        $stagingRoot = "{$batchKey}/" . Str::uuid()->toString();

        foreach ($candidates as [$file,, $filename]) {
            $relativePath = "{$stagingRoot}/{$filename}";
            $readPath = $file->getRealPath() ?: $file->getPathname();

            if (! $disk->exists($relativePath)) {
                $contents = file_get_contents($readPath);
                if ($contents === false) {
                    Cache::forget($dedupKey);

                    return;
                }
                $disk->put($relativePath, $contents);
            }

            $storedPaths[] = $relativePath;
        }

        if ($storedPaths === []) {
            Cache::forget($dedupKey);

            return;
        }

        $user = $event->user;
        $userId = (int) $user->id;
        $paths = $storedPaths;
        $processing = $this->imageProcessing;
        $eventType = $event->type;
        $contextId = $event->contextId;
        $mediaFields = $event->mediaFields;
        Log::info('all is good from here');


        return;
        Bus::batch([
            new GenerateBlurHashJob($user, $batchKey, $processing, $paths),
            [
                new CompressImageJob($user, $batchKey, $processing, $paths, $event->variant),
                new ConvertImageJob($user, $batchKey, $processing, $event->variant),
            ],
        ])
            ->before(function (Batch $batch) use ($batchKey, $userId, $mediaFields) {
                if ($mediaFields !== []) {
                    Cache::put(
                        ImagePipelineResultCache::mediaFieldsKey($batchKey, $userId, $batch->id),
                        $mediaFields,
                        ImagePipelineResultCache::ttl(),
                    );
                }
            })->progress(function (Batch $batch) use ($batchKey, $userId, $user) {
                $payload = self::batchProgressPayload($batch);

                self::publishUploadProgress($user, $batchKey, $payload, 'progress');

                Cache::put(
                    ImagePipelineResultCache::progressKey($batchKey, $userId, $batch->id),
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
            })->catch(function (Batch $batch, Throwable $e) use ($user, $batchKey) {
                Log::error('Image processing batch failed.', [
                    'batch_id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'error' => $e->getMessage(),
                ]);

                self::publishUploadProgress($user, $batchKey, [
                    'batch_id' => $batch->id,
                    'percent' => $batch->progress(),
                    'processed_jobs' => $batch->processedJobs(),
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'failed_jobs' => $batch->failedJobs,
                    'progress_bar' => null,
                ], 'failed');
            })->finally(function (Batch $batch) use ($batchKey, $userId, $variant, $stagingRoot, $dedupKey, $eventType, $contextId, $user) {
                Cache::forget($dedupKey);

                self::removeStagingTree($stagingRoot, $batchKey);

                Cache::forget(ImagePipelineResultCache::compressedPathsKey($batchKey, $userId, $variant, $batch->id));

                $blurKey = ImagePipelineResultCache::blurhashKey($batchKey, $userId, $batch->id);
                $convertKey = ImagePipelineResultCache::convertKey($batchKey, $userId, $variant, $batch->id);
                $mediaFieldsKey = ImagePipelineResultCache::mediaFieldsKey($batchKey, $userId, $batch->id);

                $blurhash = Cache::get($blurKey);
                $convertJson = Cache::get($convertKey);
                $convertPaths = is_string($convertJson)
                    ? json_decode($convertJson, true)
                    : null;

                $blurhashes = is_string($blurhash) && $blurhash !== ''
                    ? explode('|', $blurhash)
                    : [];

                $convertPathsPayload = is_array($convertPaths)
                    ? $convertPaths
                    : (is_string($convertJson) ? $convertJson : null);

                $resolvedMediaFields = Cache::get($mediaFieldsKey);
                if (! is_array($resolvedMediaFields)) {
                    $resolvedMediaFields = [];
                }

                if ($eventType === 'team') {
                    $this->addFilesRepository->addTeamFilesUrlToDb(
                        $blurhashes,
                        $convertPathsPayload,
                        $contextId,
                        $resolvedMediaFields,
                    );
                } elseif ($eventType === 'profile') {
                    $this->addFilesRepository->addProfileFilesUrlToDb($blurhashes, $convertPathsPayload, $contextId);
                } elseif ($eventType === 'post') {
                    $this->addFilesRepository->addPostFilesUrlToDb($blurhashes, $convertPathsPayload, $contextId);
                }

                Log::info('Image processing batch finished.');

                self::publishUploadProgress($user, $batchKey, [
                    'batch_id' => $batch->id,
                    'percent' => 100,
                    'processed_jobs' => $batch->totalJobs,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => 0,
                    'failed_jobs' => $batch->failedJobs,
                    'progress_bar' => '[' . str_repeat('█', 24) . '] 100%',
                ], 'completed');

                // Garder latestForUserKey pour le polling WebView (TTL 30 min dans publishUploadProgress).
                Cache::forget(ImagePipelineResultCache::progressKey($batchKey, $userId, $batch->id));
                Cache::forget($blurKey);
                Cache::forget($convertKey);
                Cache::forget($mediaFieldsKey);
            })
            ->name("image-processing:{$batchKey}")
            ->onQueue(ImageProcessingQueue::NAME)
            ->dispatch();
    }

    private static function removeStagingTree(string $stagingRoot, string $batchKey): void
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(ImageProcessingInterface::STAGING_DISK);

        if ($disk->exists($stagingRoot)) {
            $disk->deleteDirectory($stagingRoot);
        }

        if (! $disk->exists($batchKey)) {
            return;
        }

        if ($disk->directories($batchKey) === [] && $disk->files($batchKey) === []) {
            $disk->deleteDirectory($batchKey);
        }
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
    /**
     * @param  array<string, mixed>  $payload
     */
    private static function publishUploadProgress(User $user, string $batchKey, array $payload, string $status): void
    {
        $envelope = array_merge($payload, [
            'status' => $status,
            'user_id' => $user->id,
            'batch_key' => $batchKey,
        ]);

        Cache::put(
            ImagePipelineResultCache::latestForUserKey($user->id),
            $envelope,
            now()->addMinutes(30),
        );

        FileUploadBroadcast::dispatch($user, array_merge($payload, ['batch_key' => $batchKey]), $status);
    }

    private static function batchProgressPayload(Batch $batch): array
    {
        $percent = $batch->progress();
        $processed = $batch->processedJobs();

        $width = 24;
        $filled = (int) round($width * $percent / 100);
        $filled = min($width, max(0, $filled));
        $bar = '[' . str_repeat('█', $filled) . str_repeat('░', $width - $filled) . "] {$percent}%";

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
