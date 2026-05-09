<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * JPEG / PNG / GIF / WebP sans la règle Laravel {@code image} (finfo / client HTTP capricieux, ex. RapidAPI Client).
 */
class RasterImageFile implements ValidationRule
{
    /** @var list<int> */
    private const ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_GIF,
        IMAGETYPE_WEBP,
    ];

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The file must be a JPEG, PNG, GIF, or WebP image.');

            return;
        }

        if (! $value->isValid()) {
            $fail($value->getErrorMessage());

            return;
        }

        $path = $value->getPathname();
        $content = self::readUploadContents($value, $path);

        if ($content === null || $content === '') {
            $fail('The file must be a JPEG, PNG, GIF, or WebP image.');

            return;
        }

        $head = substr($content, 0, 12);
        if (self::bufferStartsWithAllowedRasterSignature($head)) {
            return;
        }

        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($content);
            if ($info !== false && in_array($info[2] ?? 0, self::ALLOWED_IMAGE_TYPES, true)) {
                return;
            }
        }

        $mimeFromBuffer = self::mimeFromBuffer($content);
        if ($mimeFromBuffer !== null && in_array($mimeFromBuffer, self::ALLOWED_MIMES, true)) {
            return;
        }

        $mime = @mime_content_type($path);
        if ($mime !== false && in_array($mime, self::ALLOWED_MIMES, true)) {
            return;
        }

        $info = @getimagesize($path);
        if ($info !== false && in_array($info[2] ?? 0, self::ALLOWED_IMAGE_TYPES, true)) {
            return;
        }

        $fail('The file must be a JPEG, PNG, GIF, or WebP image.');
    }

    private static function readUploadContents(UploadedFile $file, string $path): ?string
    {
        if ($path !== '' && is_readable($path)) {
            $raw = @file_get_contents($path);
            if ($raw !== false && $raw !== '') {
                return $raw;
            }
        }

        try {
            $raw = $file->getContent();
        } catch (Throwable) {
            return null;
        }

        return $raw !== '' ? $raw : null;
    }

    private static function mimeFromBuffer(string $content): ?string
    {
        if (! class_exists(\finfo::class)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $sample = substr($content, 0, min(65536, strlen($content)));
        $mime = $finfo->buffer($sample);

        return is_string($mime) ? $mime : null;
    }

    private static function bufferStartsWithAllowedRasterSignature(string $head): bool
    {
        if (strlen($head) < 3) {
            return false;
        }

        if ($head[0] === "\xFF" && $head[1] === "\xD8" && $head[2] === "\xFF") {
            return true;
        }

        if (str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
            return true;
        }

        if (str_starts_with($head, 'GIF87') || str_starts_with($head, 'GIF89')) {
            return true;
        }

        return strlen($head) >= 12 && str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP';
    }
}
