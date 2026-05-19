<?php

namespace App\Services\Files;

use App\Contracts\Files\ImageProcessingInterface;
use App\Enums\ImageVariantLongEdge;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use JsonException;
use kornrunner\Blurhash\Blurhash;
use RuntimeException;
use Throwable;

/**
 * Traitement d’images uploadées (flux universel backend) :
 * - correction EXIF
 * - une sortie par fichier selon {@see ImageVariantLongEdge}
 * - WebP intermédiaire sur le disque staging ; livrables finaux sous {@code temps/}
 *   ({@code {uniqueKey}__{stem}.webp}, où {@code uniqueKey} est le préfixe métier fourni à l’événement)
 *
 * Originaux et variantes : disque {@see ImageProcessingInterface::STAGING_DISK} uniquement ;
 * les livrables utilisent le disque par défaut ({@see config('filesystems.default')}).
 */
class ImageProcessing implements ImageProcessingInterface
{
    /** Petit aperçu pour BlurHash (placeholder pendant chargement). */
    private const BLURHASH_SAMPLE_EDGE = 32;

    private ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(new Driver);
    }

    /**
     * @param  list<string>  $paths  fichiers originaux sur le disque
     */
    public function generateblurhash(array $paths): string
    {
        $hashes = [];

        $staging = Storage::disk(ImageProcessingInterface::STAGING_DISK);

        foreach ($paths as $path) {
            $absolute = $staging->path($path);
            if (! is_readable($absolute)) {
                $hashes[] = '';

                continue;
            }

            try {
                $image = $this->images->decodePath($absolute);
                $image->orient();
                $image->scaleDown(width: self::BLURHASH_SAMPLE_EDGE, height: self::BLURHASH_SAMPLE_EDGE);

                $pixels = $this->toRgbGrid($image);
                if ($pixels !== []) {
                    $hashes[] = Blurhash::encode($pixels, 4, 3);
                } else {
                    $hashes[] = '';
                }
            } catch (Throwable) {
                $hashes[] = '';
            }
        }

        return implode('|', $hashes);
    }

    /**
     * @param  list<string>  $paths
     * @return list<string> un chemin WebP intermédiaire par entrée
     */
    public function compress(array $paths, ImageVariantLongEdge $variant): array
    {
        $out = [];
        $staging = Storage::disk(ImageProcessingInterface::STAGING_DISK);

        foreach ($paths as $path) {
            $absolute = $staging->path($path);
            if (! is_readable($absolute)) {
                throw new RuntimeException("Image introuvable pour compression: {$path}");
            }

            $out[] = $this->writeSingleVariant($staging, $absolute, $path, $variant);
        }

        return $out;
    }

    private function writeSingleVariant(Filesystem $staging, string $absolute, string $relativeOriginal, ImageVariantLongEdge $variant): string
    {
        $stem = pathinfo($relativeOriginal, PATHINFO_FILENAME);
        $px = $variant->value;
        $suffix = (string) $px;

        $image = $this->images->decodePath($absolute);
        $image->orient();

        if ($variant === ImageVariantLongEdge::Feed) {
            $image->scaleDown(width: $px, height: $px);
            $quality = 78;
        } else {
            $image->coverDown($px, $px);
            $quality = 72;
        }

        $batchDir = trim(str_replace('\\', '/', dirname($relativeOriginal)), '/');
        $relative = ($batchDir === '' || $batchDir === '.')
            ? "variants/{$stem}_{$suffix}.webp"
            : "{$batchDir}/variants/{$stem}_{$suffix}.webp";

        $encoded = $image->encode(new WebpEncoder(quality: $quality));
        $staging->put($relative, $encoded->toString());

        return $relative;
    }

    /**
     * @param  list<string>  $paths  sortie de {@see compress()} (WebP intermédiaires sur le disque staging)
     * @return string JSON list<string> chemins relatifs au disque par défaut (`temps/{uniqueKey}__{stem}.webp`)
     */
    public function convert(array $paths): string
    {
        $finalPaths = [];
        $staging = Storage::disk(ImageProcessingInterface::STAGING_DISK);

        foreach ($paths as $path) {
            $absolute = $staging->path($path);
            if (! is_readable($absolute)) {
                throw new RuntimeException("Image introuvable pour finalisation WebP: {$path}");
            }

            $image = $this->images->decodePath($absolute);
            $image->fillTransparentAreas('#ffffff');

            $stem = pathinfo($path, PATHINFO_FILENAME);
            $isGrid = str_ends_with($stem, '_'.ImageVariantLongEdge::GridThumb->value);

            $quality = $isGrid ? 74 : 80;
            $encoded = $image->encode(new WebpEncoder(
                quality: $quality,
                strip: true,
            ));

            $normalized = str_replace('\\', '/', $path);
            if (Str::contains($normalized, '/variants/')) {
                $batchRoot = Str::before($normalized, '/variants/');
                $safeBatch = str_replace(['/', '\\'], '_', $batchRoot);
                $relative = "temps/{$safeBatch}__{$stem}.webp";
            } else {
                $relative = "temps/{$stem}.webp";
            }

            Storage::put($relative, $encoded->toString());
            $finalPaths[] = $relative;
        }

        try {
            return json_encode($finalPaths, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return list<list{array{0: int, 1: int, 2: int}}>
     */
    private function toRgbGrid(ImageInterface $image): array
    {
        $pixels = [];
        for ($y = 0; $y < $image->height(); $y++) {
            $row = [];
            for ($x = 0; $x < $image->width(); $x++) {
                $channels = $image->colorAt($x, $y)->channels();
                $row[] = [
                    (int) ($channels[0]?->value() ?? 0),
                    (int) ($channels[1]?->value() ?? 0),
                    (int) ($channels[2]?->value() ?? 0),
                ];
            }
            $pixels[] = $row;
        }

        return $pixels;
    }
}
