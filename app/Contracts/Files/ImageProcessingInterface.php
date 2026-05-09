<?php

namespace App\Contracts\Files;

use App\Enums\ImageVariantLongEdge;

interface ImageProcessingInterface
{
    public function generateblurhash(array $paths): string;

    /**
     * @param  list<string>  $paths  originaux sur le disque (`{uniqueKey}/{hash}.ext`)
     * @return list<string> un WebP intermédiaire par entrée (`{uniqueKey}/variants/…`)
     */
    public function compress(array $paths, ImageVariantLongEdge $variant): array;

    /**
     * @param  list<string>  $paths  sortie de {@see compress()}
     * @return string JSON listant les chemins sur le disque `public` (`temps/{uniqueKey}__….webp`, plat)
     */
    public function convert(array $paths): string;
}
