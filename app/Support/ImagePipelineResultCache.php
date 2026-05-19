<?php

namespace App\Support;

use App\Enums\ImageVariantLongEdge;
use DateInterval;
use DateTimeInterface;

/**
 * Clés cache du pipeline d’images (toute {@see ImageProcessingEvent::$uniqueKey}) : progression, blurhash, convert.
 */
final class ImagePipelineResultCache
{
    public static function ttl(): DateTimeInterface|DateInterval
    {
        return now()->addHour();
    }

    public static function progressKey(string $uniqueKey, int $userId, string $batchId): string
    {
        return "image-pipeline:progress:{$uniqueKey}:{$userId}:{$batchId}";
    }

    /** Dernière progression connue pour un utilisateur (polling WebView). */
    public static function latestForUserKey(int $userId): string
    {
        return "upload-progress:latest:{$userId}";
    }

    public static function blurhashKey(string $uniqueKey, int $userId, string $batchId): string
    {
        return "image-pipeline:result:blurhash:{$uniqueKey}:{$userId}:{$batchId}";
    }

    public static function convertKey(string $uniqueKey, int $userId, ImageVariantLongEdge $variant, string $batchId): string
    {
        return "image-pipeline:result:convert:{$uniqueKey}:{$userId}:{$variant->value}:{$batchId}";
    }

    public static function compressedPathsKey(string $uniqueKey, int $userId, ImageVariantLongEdge $variant, string $batchId): string
    {
        return "image-pipeline:compressed:{$uniqueKey}:{$userId}:{$variant->value}:{$batchId}";
    }

    /** @return list<string> */
    public static function mediaFieldsKey(string $uniqueKey, int $userId, string $batchId): string
    {
        return "image-pipeline:media-fields:{$uniqueKey}:{$userId}:{$batchId}";
    }
}
