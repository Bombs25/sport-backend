<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AddFilesRepository
{
    /**
     * Écrit les médias équipe sur {@see teams} : ordre = cover puis logo
     * (voir {@see \App\Http\Controllers\Api\V1\Teams\TeamStoreController}).
     *
     * @param  list<string>  $blurhashes
     * @param  array<string, mixed>|list<mixed>|string|null  $convertPaths  JSON ou liste de chemins disque {@code public}
     */
    public function addTeamFilesUrlToDb(array $blurhashes, array|string|null $convertPaths, ?int $teamId): void
    {
        $paths = $convertPaths;
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($paths)) {
            $paths = [];
        }

        $coverPath = isset($paths[0]) && is_string($paths[0]) ? $paths[0] : null;
        $logoPath = isset($paths[1]) && is_string($paths[1]) ? $paths[1] : null;

        DB::table('teams')->where('id', (int) $teamId)->update([
            'cover_image_url' => $coverPath,
            'logo_url' => $logoPath,
            'cover_image_blurhash' => $blurhashes[0] ?? null,
            'logo_blurhash' => $blurhashes[1] ?? null,
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $blurhashes
     * @param  array<string, mixed>|list<mixed>|string|null  $convertPaths
     * @param  int|null  $userId  même valeur que {@see \App\Events\ImageProcessingEvent::$contextId} pour {@code type=profile}
     */
    public function addProfileFilesUrlToDb(array $blurhashes, array|string|null $convertPaths, ?int $userId): void
    {
        $paths = $convertPaths;
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($paths)) {
            $paths = [];
        }

        $avatarPath = isset($paths[0]) && is_string($paths[0]) ? $paths[0] : null;

        DB::table('user_profiles')->where('user_id', (int) $userId)->update([
            'avatar_url' => $avatarPath,
            'avatar_blurhash' => $blurhashes[0] ?? null,
            'updated_at' => now(),
        ]);
    }
}
