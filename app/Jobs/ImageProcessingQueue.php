<?php

namespace App\Jobs;

/**
 * File dédiée aux jobs du pipeline image (worker : php artisan queue:work --queue=image-processing).
 */
final class ImageProcessingQueue
{
    public const NAME = 'image-processing';
}
