<?php

namespace App\Services\Post;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ce qu'il fait : gère les bookmarks de l'utilisateur (`post_saves`).
 *
 * Pourquoi : feature « Save / Enregistrer un post » (icône bookmark). Modèle
 * polymorphique calqué sur `post_likes` mais simplifié :
 *  - pas de compteur global à incrémenter (privé à l'utilisateur)
 *  - pas de notification à l'auteur (convention Instagram)
 *
 * Réutilise {@see FetchPostService::regularPostBaseQuery} pour mapper les
 * lignes vers le même payload que le fil — pas de divergence de format.
 */
class PostSaveService
{
    private const PUBLICATION_TYPE_REGULAR = 'regular';

    private const PUBLICATION_TYPE_AUTOMATIC = 'automatic';

    /** @var array<int, string> */
    private const ALLOWED_PUBLICATION_TYPES = [
        self::PUBLICATION_TYPE_REGULAR,
        self::PUBLICATION_TYPE_AUTOMATIC,
    ];

    public function __construct(
        private readonly FetchPostService $fetchPostService,
    ) {}

    /**
     * Toggle save/unsave d'une publication pour un utilisateur.
     *
     * @return array{saved: bool, changed: bool}
     *
     * @throws ValidationException
     */
    public function toggleSave(
        int $publicationId,
        int $userId,
        string $publicationType,
        string $action,
    ): array {
        if (! in_array($publicationType, self::ALLOWED_PUBLICATION_TYPES, true)) {
            throw ValidationException::withMessages([
                'post_type' => 'Type de publication invalide.',
            ]);
        }

        return DB::transaction(function () use ($publicationId, $userId, $publicationType, $action): array {
            $publicationTable = $publicationType === self::PUBLICATION_TYPE_REGULAR
                ? 'posts'
                : 'match_results';
            $exists = DB::table($publicationTable)
                ->where('id', $publicationId)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'post_id' => 'Publication introuvable.',
                ]);
            }

            $existing = DB::table('post_saves')
                ->where('users_id', $userId)
                ->where('publication_id', $publicationId)
                ->where('publication_type', $publicationType)
                ->lockForUpdate()
                ->first();

            if ($action === 'save') {
                if ($existing === null) {
                    DB::table('post_saves')->insert([
                        'users_id' => $userId,
                        'publication_id' => $publicationId,
                        'publication_type' => $publicationType,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return ['saved' => true, 'changed' => true];
                }

                return ['saved' => true, 'changed' => false];
            }

            // action = 'unsave'
            if ($existing !== null) {
                DB::table('post_saves')
                    ->where('id', $existing->id)
                    ->delete();

                return ['saved' => false, 'changed' => true];
            }

            return ['saved' => false, 'changed' => false];
        });
    }

    /**
     * Liste paginée (cursor) des publications sauvegardées par l'utilisateur,
     * **tous types confondus** (regular + automatic). Préserve l'ordre desc
     * par `post_saves.id` (date de save).
     *
     * Le payload est hétérogène : chaque item porte un champ discriminant
     * `publication_type` ∈ {'regular','automatic'} pour que l'app rende le
     * composant adapté (carte texte/image vs scoreboard).
     *
     * @return array{
     *     data: Collection<int, object>,
     *     meta: array{next_cursor: int|null, has_more: bool, per_page: int}
     * }
     */
    public function listSavedPosts(int $userId, int $limit, ?int $cursor): array
    {
        $limit = max(1, min(50, $limit));

        $savedIdsQuery = $this->savedAnyPostIdsQuery($userId, $cursor, $limit + 1);
        $rows = $savedIdsQuery->get();

        $hasMore = $rows->count() > $limit;
        $page = $rows->take($limit);

        if ($page->isEmpty()) {
            return [
                'data' => collect(),
                'meta' => ['next_cursor' => null, 'has_more' => false, 'per_page' => $limit],
            ];
        }

        // Sépare les ids par type pour les fetcher en deux requêtes ciblées.
        $regularIds = [];
        $matchIds = [];
        $orderBySaveId = []; // post composite key "type:id" → position dans la liste
        foreach ($page as $i => $row) {
            $type = (string) $row->publication_type;
            $publicationId = (int) $row->publication_id;
            $orderBySaveId[$type.':'.$publicationId] = $i;
            if ($type === self::PUBLICATION_TYPE_REGULAR) {
                $regularIds[] = $publicationId;
            } else {
                $matchIds[] = $publicationId;
            }
        }

        $regularPosts = $this->fetchPostService->fetchRegularPostsByIds($userId, $regularIds);
        $matchPosts = $this->fetchPostService->fetchMatchResultsByIds($userId, $matchIds);

        // Préserve l'ordre desc des saves (et non l'ordre des sous-requêtes
        // posts/match_results qui peut diverger).
        $merged = $regularPosts
            ->concat($matchPosts)
            ->sortBy(static fn (object $p) => $orderBySaveId[
                ((string) $p->publication_type).':'.((int) $p->id)
            ] ?? PHP_INT_MAX)
            ->values();

        $nextCursor = $hasMore ? (int) $page->last()->save_id : null;

        return [
            'data' => $merged,
            'meta' => [
                'next_cursor' => $nextCursor,
                'has_more' => $hasMore,
                'per_page' => $limit,
            ],
        ];
    }

    private function savedAnyPostIdsQuery(int $userId, ?int $cursor, int $limit): Builder
    {
        $query = DB::table('post_saves')
            ->where('users_id', $userId)
            ->whereIn('publication_type', self::ALLOWED_PUBLICATION_TYPES)
            ->orderByDesc('id')
            ->limit($limit)
            ->select(['id as save_id', 'publication_id', 'publication_type']);

        if ($cursor !== null && $cursor > 0) {
            $query->where('id', '<', $cursor);
        }

        return $query;
    }
}
