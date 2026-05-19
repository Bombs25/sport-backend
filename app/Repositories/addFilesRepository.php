<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Api\V1\Teams\TeamStoreController;
use App\Services\Search\TypesenseTeamService;
use App\Services\Search\TypesenseUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Typesense\Exceptions\TypesenseClientError;

class AddFilesRepository
{
    public function __construct(
        private readonly TypesenseTeamService $typesenseTeams,
        private readonly TypesenseUserService $typesenseUsers,
    ) {}

    /**
     * Écrit les médias équipe sur {@see teams} : ordre = cover puis logo
     * (voir {@see TeamStoreController}).
     *
     * @param  list<string>  $blurhashes
     * @param  array<string, mixed>|list<mixed>|string|null  $convertPaths  JSON ou liste de chemins sur le disque par défaut
     */
    /**
     * @param  list<string>  $mediaFields  champs alignés sur {@see $convertPaths} (ex. mise à jour logo seul)
     */
    public function addTeamFilesUrlToDb(
        array $blurhashes,
        array|string|null $convertPaths,
        ?int $teamId,
        array $mediaFields = [],
    ): void {
        $paths = $convertPaths;
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($paths)) {
            $paths = [];
        }

        $updates = ['updated_at' => now()];

        if ($mediaFields !== []) {
            foreach ($mediaFields as $index => $field) {
                if (! is_string($field) || ! isset($paths[$index]) || ! is_string($paths[$index])) {
                    continue;
                }

                if ($field === 'cover_image_url') {
                    $updates['cover_image_url'] = $paths[$index];
                    $updates['cover_image_blurhash'] = $blurhashes[$index] ?? null;
                } elseif ($field === 'logo_url') {
                    $updates['logo_url'] = $paths[$index];
                    $updates['logo_blurhash'] = $blurhashes[$index] ?? null;
                }
            }
        } else {
            $updates['cover_image_url'] = isset($paths[0]) && is_string($paths[0]) ? $paths[0] : null;
            $updates['logo_url'] = isset($paths[1]) && is_string($paths[1]) ? $paths[1] : null;
            $updates['cover_image_blurhash'] = $blurhashes[0] ?? null;
            $updates['logo_blurhash'] = $blurhashes[1] ?? null;
        }

        DB::table('teams')->where('id', (int) $teamId)->update($updates);

        try {
            $this->typesenseTeams->syncTeamFromDatabase((int) $teamId);
        } catch (TypesenseClientError $e) {
            Log::warning('Typesense team media sync failed.', [
                'team_id' => $teamId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<string>  $blurhashes
     * @param  array<string, mixed>|list<mixed>|string|null  $convertPaths
     * @param  int|null  $userId  même valeur que {@see ImageProcessingEvent::$contextId} pour {@code type=profile}
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

        try {
            $this->typesenseUsers->syncUserFromDatabase((int) $userId);
        } catch (TypesenseClientError $e) {
            Log::warning('Typesense profile avatar sync failed.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<string>  $blurhashes
     * @param  array<string, mixed>|list<mixed>|string|null  $convertPaths
     */
    public function addPostFilesUrlToDb(array $blurhashes, array|string|null $convertPaths, ?int $postId): void
    {
        $paths = $convertPaths;
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($paths)) {
            $paths = [];
        }

        $now = now();
        $rows = [];
        foreach (array_values($paths) as $position => $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $rows[] = [
                'post_id' => (int) $postId,
                'position' => $position,
                'path' => $path,
                'blurhash' => $blurhashes[$position] ?? null,
                'alt_text' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($postId, $rows): void {
            if ($rows !== []) {
                DB::table('post_media')->upsert(
                    $rows,
                    ['post_id', 'position'],
                    ['path', 'blurhash', 'alt_text', 'updated_at'],
                );
            }

            DB::table('posts')
                ->where('id', (int) $postId)
                ->update([
                    'media_count' => count($rows),
                    'updated_at' => now(),
                ]);
        });
    }
}
