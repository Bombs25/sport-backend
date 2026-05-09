<?php

namespace App\Enums;

/**
 * Grand côté (flux) ou carré grille pour une seule sortie pipeline.
 */
enum ImageVariantLongEdge: int
{
    /** Variante « flux » : scaleDown sur le grand côté. */
    case Feed = 1080;

    /** Miniature carrée : coverDown sur ce côté. */
    case GridThumb = 360;
}
