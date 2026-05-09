<?php

namespace App\Support;

use App\Enums\ImageVariantLongEdge;
use DateInterval;
use DateTimeInterface;

/**
 * Clés / TTL : progression du lot, résultats blurhash / convert (`finally`).
 */
final class ImagePipelineResultCache
{
    public static function ttl(): DateTimeInterface|DateInterval
    {
        return now()->addHour();
    }

    public static function progressKey(string $uniqueKey, int $userId): string
    {
        return "image-pipeline:progress:{$uniqueKey}:{$userId}";
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
