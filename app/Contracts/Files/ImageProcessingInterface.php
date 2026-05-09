<?php

namespace App\Contracts\Files;

use App\Enums\ImageVariantLongEdge;

interface ImageProcessingInterface
{
    public function generateblurhash(array $paths): string;

    /**
     * @param  list<string>  $paths  originaux sur le disque
     * @return list<string> un fichier WebP intermédiaire par entrée (`variants/…`)
     */
    public function compress(array $paths, ImageVariantLongEdge $variant): array;

    /**
     * @param  list<string>  $paths  sortie de {@see compress()}
     * @return string JSON encodant la liste des chemins WebP finaux (livraison client / Expo)
     */
    public function convert(array $paths): string;
}
