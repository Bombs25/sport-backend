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
 * Traitement d'images directement avec S3.
 *
 * Aucun fichier temporaire local n'est utilisé.
 *
 * Flux général :
 *
 *   S3
 *    ↓
 * Storage::get()
 *    ↓
 * données binaires en mémoire
 *    ↓
 * Intervention Image
 *    ↓
 * traitement en mémoire
 *    ↓
 * WebP en mémoire
 *    ↓
 * Storage::put()
 *    ↓
 * S3
 *
 * Compatible Intervention Image 4.x.
 */
class ImageProcessing implements ImageProcessingInterface
{
        /**
         * Taille du petit aperçu utilisé pour le BlurHash.
         */
        private const BLURHASH_SAMPLE_EDGE = 32;

        /**
         * Gestionnaire Intervention Image.
         *
         * Le traitement est effectué en mémoire avec GD.
         */
        private ImageManager $images;

        public function __construct()
        {
                $this->images = new ImageManager(
                        new Driver()
                );
        }

        /**
         * Génère un BlurHash pour chaque image présente sur S3.
         *
         * Aucun fichier local n'est créé.
         *
         * @param  list<string>  $paths
         */
        public function generateblurhash(array $paths): string
        {
                $hashes = [];

                /*
         * On utilise directement le disque S3 configuré
         * dans ImageProcessingInterface::STAGING_DISK.
         */
                $staging = Storage::disk(
                        ImageProcessingInterface::STAGING_DISK
                );

                foreach ($paths as $path) {
                        /*
             * Vérification directement sur S3.
             *
             * On ne doit PAS utiliser :
             *
             * $staging->path($path)
             * is_readable(...)
             */
                        if (! $staging->exists($path)) {
                                $hashes[] = '';

                                continue;
                        }

                        try {
                                /*
                 * Récupération du fichier directement depuis S3.
                 *
                 * $contents contient les données binaires de l'image.
                 */
                                $contents = $staging->get($path);

                                if ($contents === null || $contents === '') {
                                        $hashes[] = '';

                                        continue;
                                }

                                /*
                 * Intervention Image 4.x :
                 *
                 * decode() accepte notamment les données binaires
                 * brutes de l'image.
                 *
                 * Aucun fichier temporaire n'est nécessaire.
                 */
                                $image = $this->images->decode($contents);

                                /*
                 * Correction de l'orientation EXIF.
                 */
                                $image->orient();

                                /*
                 * Réduction en mémoire uniquement pour le BlurHash.
                 */
                                $image->scaleDown(
                                        width: self::BLURHASH_SAMPLE_EDGE,
                                        height: self::BLURHASH_SAMPLE_EDGE
                                );

                                /*
                 * Transformation de l'image en grille RGB.
                 */
                                $pixels = $this->toRgbGrid($image);

                                if ($pixels !== []) {
                                        $hashes[] = Blurhash::encode(
                                                $pixels,
                                                4,
                                                3
                                        );
                                } else {
                                        $hashes[] = '';
                                }
                        } catch (Throwable) {
                                /*
                 * Une erreur sur une image ne doit pas empêcher
                 * la génération des BlurHash des autres images.
                 */
                                $hashes[] = '';
                        }
                }

                return implode('|', $hashes);
        }

        /**
         * Génère une variante WebP intermédiaire directement sur S3.
         *
         * @param  list<string>  $paths
         * @return list<string>
         */
        public function compress(
                array $paths,
                ImageVariantLongEdge $variant
        ): array {
                /*
                 * ---------------------------------------------------------
                 * Compression temporairement désactivée
                 * ---------------------------------------------------------
                 *
                 * Ancien traitement mis en pause.
                 *
                 * $out = [];

                 * $staging = Storage::disk(
                 *         ImageProcessingInterface::STAGING_DISK
                 * );

                 * foreach ($paths as $path) {
                 *         if (! $staging->exists($path)) {
                 *                 throw new RuntimeException(
                 *                         "Image introuvable dans S3 pour compression: {$path}"
                 *                 );
                 *         }

                 *         $out[] = $this->writeSingleVariant(
                 *                 $staging,
                 *                 $path,
                 *                 $variant
                 *         );
                 * }

                 * return $out;
                 */

                // On conserve le même contrat :
                // les chemins originaux sont simplement retournés.
                return $paths;
        }

        /**
         * Génère une seule variante WebP.
         *
         * Aucun fichier local n'est créé.
         *
         * @param  Filesystem  $staging
         * @param  string  $relativeOriginal
         * @param  ImageVariantLongEdge  $variant
         */
        private function writeSingleVariant(
                Filesystem $staging,
                string $relativeOriginal,
                ImageVariantLongEdge $variant
        ): string {
                /*
         * ---------------------------------------------------------
         * 1. Lecture directe depuis S3
         * ---------------------------------------------------------
         */
                $contents = $staging->get($relativeOriginal);

                if ($contents === null || $contents === '') {
                        throw new RuntimeException(
                                "Impossible de lire l'image depuis S3: {$relativeOriginal}"
                        );
                }

                /*
         * ---------------------------------------------------------
         * 2. Décodage depuis les données binaires
         * ---------------------------------------------------------
         *
         * Intervention Image 4.x utilise decode().
         *
         * NE PAS utiliser :
         *
         * decodePath()
         *
         * car nous ne disposons volontairement d'aucun chemin
         * local pour cette architecture S3.
         */
                $image = $this->images->decode($contents);

                /*
         * Correction de l'orientation EXIF.
         */
                $image->orient();

                /*
         * ---------------------------------------------------------
         * 3. Resize / crop
         * ---------------------------------------------------------
         */
                $px = $variant->value;

                if ($variant === ImageVariantLongEdge::Feed) {
                        /*
             * Feed :
             * conserve les proportions et limite l'image
             * à $px x $px.
             */
                        $image->scaleDown(
                                width: $px,
                                height: $px
                        );

                        $quality = 78;
                } else {
                        /*
             * Autres variantes :
             * recadrage carré.
             */
                        $image->coverDown(
                                $px,
                                $px
                        );

                        $quality = 72;
                }

                /*
         * ---------------------------------------------------------
         * 4. Construction du chemin de sortie S3
         * ---------------------------------------------------------
         */
                $stem = pathinfo(
                        $relativeOriginal,
                        PATHINFO_FILENAME
                );

                $suffix = (string) $px;

                $batchDir = trim(
                        str_replace(
                                '\\',
                                '/',
                                dirname($relativeOriginal)
                        ),
                        '/'
                );

                if ($batchDir === '' || $batchDir === '.') {
                        $relative = "variants/{$stem}_{$suffix}.webp";
                } else {
                        $relative = "{$batchDir}/variants/{$stem}_{$suffix}.webp";
                }

                /*
         * ---------------------------------------------------------
         * 5. Encodage WebP en mémoire
         * ---------------------------------------------------------
         */
                $encoded = $image->encode(
                        new WebpEncoder(
                                quality: $quality
                        )
                );

                /*
         * ---------------------------------------------------------
         * 6. Upload direct vers S3
         * ---------------------------------------------------------
         *
         * toString() retourne les données binaires du WebP.
         *
         * Aucun fichier local n'est créé.
         */
                $success = $staging->put(
                        $relative,
                        $encoded->toString()
                );

                if (! $success) {
                        throw new RuntimeException(
                                "Impossible d'écrire la variante WebP dans S3: {$relative}"
                        );
                }

                return $relative;
        }

        /**
         * Finalise les variantes WebP.
         *
         * Les variantes intermédiaires sont déjà présentes sur S3.
         *
         * Elles sont :
         *
         *   S3
         *    ↓
         *   get()
         *    ↓
         *   mémoire
         *    ↓
         *   traitement
         *    ↓
         *   WebP
         *    ↓
         *   put()
         *    ↓
         *   S3
         *
         * @param  list<string>  $paths
         * @return string JSON list<string>
         */
        public function convert(array $paths): string
        {
                /*
                 * ---------------------------------------------------------
                 * Conversion temporairement désactivée
                 * ---------------------------------------------------------
                 *
                 * Ancien traitement mis en pause.
                 *
                 * $finalPaths = [];

                 * $staging = Storage::disk(
                 *         ImageProcessingInterface::STAGING_DISK
                 * );

                 * foreach ($paths as $path) {
                 *         if (! $staging->exists($path)) {
                 *                 throw new RuntimeException(
                 *                         "Image introuvable dans S3 pour finalisation WebP: {$path}"
                 *                 );
                 *         }

                 *         $contents = $staging->get($path);

                 *         if ($contents === null || $contents === '') {
                 *                 throw new RuntimeException(
                 *                         "Impossible de lire le WebP intermédiaire depuis S3: {$path}"
                 *                 );
                 *         }

                 *         $image = $this->images->decode($contents);
                 *         $image->fillTransparentAreas('#ffffff');

                 *         $stem = pathinfo(
                 *                 $path,
                 *                 PATHINFO_FILENAME
                 *         );

                 *         $isGrid = str_ends_with(
                 *                 $stem,
                 *                 '_' . ImageVariantLongEdge::GridThumb->value
                 *         );

                 *         $quality = $isGrid ? 74 : 80;

                 *         $encoded = $image->encode(
                 *                 new WebpEncoder(
                 *                         quality: $quality,
                 *                         strip: true,
                 *                 )
                 *         );

                 *         $normalized = str_replace(
                 *                 '\\',
                 *                 '/',
                 *                 $path
                 *         );

                 *         if (Str::contains($normalized, '/variants/')) {
                 *                 $batchRoot = Str::before(
                 *                         $normalized,
                 *                         '/variants/'
                 *                 );

                 *                 $safeBatch = str_replace(
                 *                         ['/', '\\'],
                 *                         '_',
                 *                         $batchRoot
                 *                 );

                 *                 $relative = "temps/{$safeBatch}__{$stem}.webp";
                 *         } else {
                 *                 $relative = "temps/{$stem}.webp";
                 *         }

                 *         $success = $staging->put(
                 *                 $relative,
                 *                 $encoded->toString()
                 *         );

                 *         if (! $success) {
                 *                 throw new RuntimeException(
                 *                         "Impossible d'écrire l'image finale dans S3: {$relative}"
                 *                 );
                 *         }

                 *         $finalPaths[] = $relative;
                 * }
                 */

                // On conserve le même contrat :
                // les chemins reçus sont retournés tels quels en JSON.
                try {
                        return json_encode(
                                $paths,
                                JSON_THROW_ON_ERROR
                        );
                } catch (JsonException $e) {
                        throw new RuntimeException(
                                $e->getMessage(),
                                0,
                                $e
                        );
                }
        }

        /**
         * Transforme une image en grille RGB pour BlurHash.
         *
         * Tout est calculé en mémoire.
         *
         * @return list<list<array{0: int, 1: int, 2: int}>>
         */
        private function toRgbGrid(
                ImageInterface $image
        ): array {
                $pixels = [];

                for (
                        $y = 0;
                        $y < $image->height();
                        $y++
                ) {
                        $row = [];

                        for (
                                $x = 0;
                                $x < $image->width();
                                $x++
                        ) {
                                /*
                 * Récupération des canaux RGB.
                 */
                                $channels = $image
                                        ->colorAt($x, $y)
                                        ->channels();

                                $row[] = [
                                        (int) (
                                                $channels[0]?->value() ?? 0
                                        ),
                                        (int) (
                                                $channels[1]?->value() ?? 0
                                        ),
                                        (int) (
                                                $channels[2]?->value() ?? 0
                                        ),
                                ];
                        }

                        $pixels[] = $row;
                }

                return $pixels;
        }
}
