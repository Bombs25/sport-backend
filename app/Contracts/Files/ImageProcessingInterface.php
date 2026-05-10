<?php

namespace App\Contracts\Files;

use App\Enums\ImageVariantLongEdge;

interface ImageProcessingInterface
{
    /** Disque Laravel pour originaux / variantes (hors {@code storage/app/public/}). */
    public const STAGING_DISK = 'local';

    public function generateblurhash(array $paths): string;

    /**
     * @param  list<string>  $paths  originaux sur le disque staging `local` (`{uniqueKey}/{runId}/{hash}.ext` ou équivalent choisi par l’appelant)
     * @return list<string> un WebP intermédiaire par entrée (même arborescence relative + {@code variants/})
     */
    public function compress(array $paths, ImageVariantLongEdge $variant): array;

    /**
     * @param  list<string>  $paths  sortie de {@see compress()}
     * @return string JSON listant les chemins sur le disque `public` (préfixe dérivé du chemin staging → {@code temps/…}.webp)
     */
    public function convert(array $paths): string;
}
