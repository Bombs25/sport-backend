<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicImageUrl
{
    public static function from(mixed $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::url($path);
    }
}
