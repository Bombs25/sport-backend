<?php

namespace App\Contracts\Files;

use App\Enums\ImageVariantLongEdge;

interface ImageProcessingInterface
{
    /** Disque privé pour originaux / variantes (jamais le disque par défaut des livrables). */
    public const STAGING_DISK = 's3';

    public function generateblurhash(array $paths): string;

    /**
     * @param  list<string>  $paths  originaux sur le disque staging `local` (`{uniqueKey}/{runId}/{hash}.ext` ou équivalent choisi par l’appelant)
     * @return list<string> un WebP intermédiaire par entrée (même arborescence relative + {@code variants/})
     */
    public function compress(array $paths, ImageVariantLongEdge $variant): array;

    /**
     * @param  list<string>  $paths  sortie de {@see compress()}
     * @return string JSON listant les chemins sur le disque par défaut (préfixe dérivé du chemin staging → {@code temps/…}.webp)
     */
    public function convert(array $paths): string;
}
