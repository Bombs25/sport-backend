<?php

namespace App\Support;

use App\Enums\ImageVariantLongEdge;
use DateInterval;
use DateTimeInterface;

/**
 * Clés / TTL pour les sorties finales du lot (blurhash, JSON convert), lues dans le `finally` du batch.
 */
final class ImagePipelineResultCache
{
    public static function ttl(): DateTimeInterface|DateInterval
    {
        return now()->addHour();
    }

    public static function blurhashKey(string $uniqueKey, int $userId): string
    {
        return "image-pipeline:result:blurhash:{$uniqueKey}:{$userId}";
    }

    public static function convertKey(string $uniqueKey, int $userId, ImageVariantLongEdge $variant): string
    {
        return "image-pipeline:result:convert:{$uniqueKey}:{$userId}:{$variant->value}";
    }
}
