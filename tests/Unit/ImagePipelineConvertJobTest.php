<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\ConvertImageJob;
use PHPUnit\Framework\TestCase;

class ImagePipelineConvertJobTest extends TestCase
{
    public function test_convert_image_job_class_is_autoloaded(): void
    {
        self::assertTrue(class_exists(ConvertImageJob::class));
    }
}
