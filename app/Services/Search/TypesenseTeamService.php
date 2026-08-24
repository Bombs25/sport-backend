<?php

namespace App\Services\Search;

use App\Support\PublicImageUrl;
use App\Support\Search\TypesenseSyncGuard;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

use Http\Client\Curl\Client as CurlClient;
use Http\Discovery\Psr17FactoryDiscovery;

class TypesenseTeamService
{
    private Client $client;

    /** @var array{name: string, fields: list<array<string, mixed>>, default_sorting_field: string} */
    private const SCHEMA = [
        'name' => 'teams',
        'fields' => [
            ['name' => 'id', 'type' => 'int64'],
            ['name' => 'creator_id', 'type' => 'int64', 'facet' => true],
            ['name' => 'name', 'type' => 'string'],
            ['name' => 'slug', 'type' => 'string'],
            ['name' => 'sport_id', 'type' => 'int64', 'facet' => true],
            ['name' => 'sport_name', 'type' => 'string', 'facet' => true],
            ['name' => 'sport_slug', 'type' => 'string', 'facet' => true],
            ['name' => 'competition_type', 'type' => 'string', 'facet' => true],
            ['name' => 'skill_level', 'type' => 'string', 'facet' => true, 'optional' => true],
            ['name' => 'description', 'type' => 'string', 'optional' => true],
            ['name' => 'hq_city', 'type' => 'string', 'facet' => true, 'optional' => true],
            ['name' => 'hq_latitude', 'type' => 'float', 'optional' => true, 'index' => false],
            ['name' => 'hq_longitude', 'type' => 'float', 'optional' => true, 'index' => false],
            ['name' => 'cover_image_url', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'logo_url', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'cover_image_blurhash', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'logo_blurhash', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'members_count', 'type' => 'int32'],
            ['name' => 'location', 'type' => 'geopoint', 'optional' => true],
            ['name' => 'created_at', 'type' => 'int64'],
        ],
        'default_sorting_field' => 'created_at',
    ];

    // public function __construct()
    // {
    //     $t = config('services.typesense');

    //     $this->client = new Client([
    //         'api_key' => $t['api_key'],
    //         'nodes' => [
    //             [
    //                 'host' => $t['host'],
    //                 'port' => $t['port'],
    //                 'protocol' => $t['protocol'],
    //             ],
    //         ],
    //         'connection_timeout_seconds' => 10,
    //     ]);
    // }

    public function __construct()
    {
        $t = config('services.typesense');

        $curlClient = new CurlClient(
            Psr17FactoryDiscovery::findResponseFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $this->client = new Client([
            'api_key' => $t['api_key'],
            'nodes' => [
                [
                    'host' => $t['host'],
                    'port' => $t['port'],
                    'protocol' => $t['protocol'],
                ],
            ],
            'connection_timeout_seconds' => 10,
            'client' => $curlClient,
        ]);
    }

    /**
     * @return array{name: string, fields: list<array<string, mixed>>, default_sorting_field: string}
     */
    public static function schema(): array
    {
        return self::SCHEMA;
    }

    /**
     * @throws TypesenseClientError
     */
    public function ensureCollection(): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $this->client->collections['teams']->retrieve();
        } catch (ObjectNotFound) {
            $this->client->collections->create(self::SCHEMA);
        }
    }

    /**
     * Recrée la collection `teams` pour garantir que le schéma Typesense suit le code avant un seed.
     *
     * @throws TypesenseClientError
     */
    public function recreateCollection(): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $this->client->collections['teams']->delete();
        } catch (ObjectNotFound) {
            //
        }

        $this->client->collections->create(self::SCHEMA);
    }

    /**
     * @param  list<array{
     *     id: string,
     *     creator_id: int,
     *     name: string,
     *     slug: string,
     *     sport_id: int,
     *     sport_name: string,
     *     sport_slug: string,
     *     competition_type: string,
     *     skill_level?: string,
     *     description?: string,
     *     hq_city?: string,
     *     hq_latitude?: float,
     *     hq_longitude?: float,
     *     cover_image_url?: string,
     *     logo_url?: string,
     *     cover_image_blurhash?: string,
     *     logo_blurhash?: string,
     *     members_count: int,
     *     location?: array{float, float},
     *     created_at: int,
     * }>  $documents
     *
     * @throws TypesenseClientError
     */
    public function importDocuments(array $documents): void
    {
        if ($documents === [] || ! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        $result = $this->client->collections['teams']->documents->import(
            $documents,
            ['action' => 'upsert'],
        );

        Log::info('Import Typesense teams terminé.', [
            'count' => count($documents),
            'documents' => $result,
        ]);
    }

    /**
     * @throws TypesenseClientError
     */
    public function syncTeamFromDatabase(int $teamId): void
    {
        $document = $this->documentForTeam($teamId);

        if ($document === null) {
            return;
        }

        $this->importDocuments([$document]);
    }

    /**
     * Retire une équipe de l'index Typesense (idempotent si le document est absent).
     *
     * @throws TypesenseClientError
     */
    public function deleteTeamFromIndex(int $teamId): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $this->client->collections['teams']->documents[(string) $teamId]->delete();
        } catch (ObjectNotFound) {
            //
        }
    }

    /**
     * @throws TypesenseClientError
     */
    public function syncAllTeamsFromDatabase(int $chunkSize = 1000): int
    {
        $this->ensureCollection();

        $synced = 0;

        $this->teamDocumentQuery()
            ->orderBy('teams.id')
            ->chunkById($chunkSize, function ($rows) use (&$synced): void {
                $documents = [];

                foreach ($rows as $row) {
                    $documents[] = $this->documentFromDatabaseRow($row);
                }

                $this->importDocuments($documents);
                $synced += count($documents);
            }, 'teams.id', 'id');

        return $synced;
    }

    /**
     * Resynchronise les équipes dont la position Typesense dépend du profil créateur.
     *
     * @throws TypesenseClientError
     */
    public function syncTeamsForCreatorFromDatabase(int $creatorId, int $chunkSize = 1000): int
    {
        $this->ensureCollection();

        $synced = 0;

        $this->teamDocumentQuery()
            ->where('teams.creator_id', $creatorId)
            ->orderBy('teams.id')
            ->chunkById($chunkSize, function ($rows) use (&$synced): void {
                $documents = [];

                foreach ($rows as $row) {
                    $documents[] = $this->documentFromDatabaseRow($row);
                }

                $this->importDocuments($documents);
                $synced += count($documents);
            }, 'teams.id', 'id');

        return $synced;
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     *
     * @throws TypesenseClientError
     */
    public function searchPublicTeamsAround(
        float $latitude,
        float $longitude,
        string $query = '*',
        ?int $sportId = null,
        ?string $competitionType = null,
        ?string $skillLevel = null,
        float $radiusKm = 100.0,
        int $page = 1,
        int $perPage = 10,
        ?int $excludeCreatorId = null,
        array $excludeTeamIds = [],
    ): array {
        $q = $this->normalizeQuery($query);
        $lat = $this->formatGeoNumber($latitude);
        $lng = $this->formatGeoNumber($longitude);
        $radius = $this->formatGeoNumber($radiusKm);
        $filters = [
            //"location:({$lat}, {$lng}, {$radius} km)",
        ];

        if ($sportId !== null) {
            $filters[] = 'sport_id:=' . $sportId;
        }

        if ($competitionType !== null && $competitionType !== '') {
            $filters[] = 'competition_type:=' . $competitionType;
        }

        if ($skillLevel !== null && $skillLevel !== '') {
            $filters[] = 'skill_level:=' . $skillLevel;
        }

        $filters = array_merge($filters, self::buildSearchExclusionFilters($excludeCreatorId, $excludeTeamIds));

        $response = $this->client->collections['teams']->documents->search([
            'q' => $q,
            'query_by' => 'name,slug,sport_name,sport_slug,description,hq_city',
            'filter_by' => implode(' && ', $filters),
            'sort_by' => "location({$lat}, {$lng}):asc",
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $found = (int) ($response['found'] ?? 0);
        $currentPage = (int) ($response['page'] ?? $page);
        $nextPage = $currentPage * $perPage < $found ? $currentPage + 1 : null;

        return [
            'data' => $this->formatSearchHits($response['hits'] ?? []),
            'meta' => [
                'found' => $found,
                'out_of' => (int) ($response['out_of'] ?? 0),
                'page' => $currentPage,
                'next_page' => $nextPage,
                'per_page' => $perPage,
                'search_time_ms' => (int) ($response['search_time_ms'] ?? 0),
                'center' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius_km' => $radiusKm,
                ],
            ],
        ];
    }

    private const RANKING_SEARCH_MAX_HITS = 250;

    /**
     * IDs d'équipes correspondant à une recherche texte (classement), sans géolocalisation.
     *
     * @return array{ids: list<int>, found: int}
     *
     * @throws TypesenseClientError
     */
    public function searchTeamIdsForRanking(string $query, int $sportId): array
    {
        $q = $this->normalizeQuery($query);
        if ($q === '*') {
            return ['ids' => [], 'found' => 0];
        }

        $response = $this->client->collections['teams']->documents->search([
            'q' => $q,
            'query_by' => 'name,slug',
            'filter_by' => 'sport_id:=' . $sportId,
            'page' => 1,
            'per_page' => self::RANKING_SEARCH_MAX_HITS,
        ]);

        $ids = [];
        foreach ($response['hits'] ?? [] as $hit) {
            $document = $hit['document'] ?? [];
            if (! isset($document['id'])) {
                continue;
            }
            $ids[] = (int) $document['id'];
        }

        return [
            'ids' => $ids,
            'found' => (int) ($response['found'] ?? count($ids)),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     creator_id: int,
     *     name: string,
     *     slug: string,
     *     sport_id: int,
     *     sport_name: string,
     *     sport_slug: string,
     *     competition_type: string,
     *     skill_level?: string,
     *     description?: string,
     *     hq_city?: string,
     *     hq_latitude?: float,
     *     hq_longitude?: float,
     *     cover_image_url?: string,
     *     logo_url?: string,
     *     cover_image_blurhash?: string,
     *     logo_blurhash?: string,
     *     members_count: int,
     *     location?: array{float, float},
     *     created_at: int,
     * }|null
     */
    private function documentForTeam(int $teamId): ?array
    {
        $row = $this->teamDocumentQuery()
            ->where('teams.id', $teamId)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->documentFromDatabaseRow($row);
    }

    private function teamDocumentQuery(): Builder
    {
        return DB::table('teams')
            ->join('sports', 'sports.id', '=', 'teams.sport_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'teams.creator_id')
            ->select([
                'teams.id',
                'teams.creator_id',
                'teams.name',
                'teams.slug',
                'teams.sport_id',
                'teams.competition_type',
                'teams.skill_level',
                'teams.description',
                'teams.hq_city',
                'teams.hq_latitude',
                'teams.hq_longitude',
                'teams.cover_image_url',
                'teams.logo_url',
                'teams.cover_image_blurhash',
                'teams.logo_blurhash',
                'teams.created_at',
                'sports.name as sport_name',
                'sports.slug as sport_slug',
                DB::raw('(SELECT COUNT(*) FROM `team_members` WHERE `team_members`.`team_id` = `teams`.`id` AND `team_members`.`status` = "active") AS `members_count`'),
                DB::raw('CASE WHEN user_profiles.`location` IS NULL THEN NULL ELSE ST_Latitude(user_profiles.`location`) END AS `latitude`'),
                DB::raw('CASE WHEN user_profiles.`location` IS NULL THEN NULL ELSE ST_Longitude(user_profiles.`location`) END AS `longitude`'),
            ]);
    }

    /**
     * @param  list<array<string, mixed>>  $hits
     * @return list<array<string, mixed>>
     */
    private function formatSearchHits(array $hits): array
    {
        $results = [];

        foreach ($hits as $hit) {
            $document = is_array($hit['document'] ?? null) ? $hit['document'] : [];
            $location = is_array($document['location'] ?? null) ? $document['location'] : null;
            $meters = data_get($hit, 'geo_distance_meters.location');

            $results[] = [
                'id' => (int) ($document['id'] ?? 0),
                'creator_id' => (int) ($document['creator_id'] ?? 0),
                'name' => (string) ($document['name'] ?? ''),
                'slug' => (string) ($document['slug'] ?? ''),
                'sport_id' => (int) ($document['sport_id'] ?? 0),
                'sport' => [
                    'id' => (int) ($document['sport_id'] ?? 0),
                    'name' => (string) ($document['sport_name'] ?? ''),
                    'slug' => (string) ($document['sport_slug'] ?? ''),
                ],
                'competition_type' => (string) ($document['competition_type'] ?? ''),
                'skill_level' => $document['skill_level'] ?? null,
                'description' => $document['description'] ?? null,
                'hq_city' => $document['hq_city'] ?? null,
                'hq_latitude' => isset($document['hq_latitude']) ? (float) $document['hq_latitude'] : null,
                'hq_longitude' => isset($document['hq_longitude']) ? (float) $document['hq_longitude'] : null,
                'latitude' => $location !== null && array_key_exists(0, $location) ? (float) $location[0] : null,
                'longitude' => $location !== null && array_key_exists(1, $location) ? (float) $location[1] : null,
                'cover_image_url' => PublicImageUrl::from($document['cover_image_url'] ?? null),
                'logo_url' => PublicImageUrl::from($document['logo_url'] ?? null),
                'cover_image_blurhash' => $document['cover_image_blurhash'] ?? null,
                'logo_blurhash' => $document['logo_blurhash'] ?? null,
                'members_count' => (int) ($document['members_count'] ?? 0),
                'distance_meters' => is_numeric($meters) ? (int) $meters : null,
                'distance_km' => is_numeric($meters) ? round(((float) $meters) / 1000, 1) : null,
            ];
        }

        return $results;
    }

    /**
     * Filtres Typesense pour exclure les équipes du viewer (créées ou dont il est membre).
     *
     * @param  list<int>  $excludeTeamIds
     * @return list<string>
     */
    public static function buildSearchExclusionFilters(?int $excludeCreatorId, array $excludeTeamIds): array
    {
        $filters = [];

        if ($excludeCreatorId !== null) {
            $filters[] = 'creator_id:!=' . $excludeCreatorId;
        }

        $ids = array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int) $id, $excludeTeamIds),
            static fn(int $id): bool => $id > 0,
        )));

        if ($ids !== []) {
            $filters[] = 'id:!=[' . implode(', ', $ids) . ']';
        }

        return $filters;
    }

    private function formatGeoNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');
    }

    private function normalizeQuery(string $query): string
    {
        $q = trim($query);

        if ($q === '' || in_array(strtolower($q), ['null', 'undefined'], true)) {
            return '*';
        }

        return $q;
    }

    /**
     * @return array{
     *     id: string,
     *     creator_id: int,
     *     name: string,
     *     slug: string,
     *     sport_id: int,
     *     sport_name: string,
     *     sport_slug: string,
     *     competition_type: string,
     *     skill_level?: string,
     *     description?: string,
     *     hq_city?: string,
     *     hq_latitude?: float,
     *     hq_longitude?: float,
     *     cover_image_url?: string,
     *     logo_url?: string,
     *     cover_image_blurhash?: string,
     *     logo_blurhash?: string,
     *     members_count: int,
     *     location?: array{float, float},
     *     created_at: int,
     * }
     */
    private function documentFromDatabaseRow(stdClass $row): array
    {
        $document = [
            'id' => (string) $row->id,
            'creator_id' => (int) $row->creator_id,
            'name' => (string) $row->name,
            'slug' => (string) $row->slug,
            'sport_id' => (int) $row->sport_id,
            'sport_name' => (string) $row->sport_name,
            'sport_slug' => (string) $row->sport_slug,
            'competition_type' => (string) $row->competition_type,
            'members_count' => (int) $row->members_count,
            'created_at' => strtotime((string) $row->created_at) ?: now()->timestamp,
        ];

        if ($row->skill_level !== null) {
            $document['skill_level'] = (string) $row->skill_level;
        }

        if ($row->description !== null) {
            $document['description'] = (string) $row->description;
        }

        if ($row->hq_city !== null) {
            $document['hq_city'] = (string) $row->hq_city;
        }

        if ($row->hq_latitude !== null) {
            $document['hq_latitude'] = (float) $row->hq_latitude;
        }

        if ($row->hq_longitude !== null) {
            $document['hq_longitude'] = (float) $row->hq_longitude;
        }

        if ($row->cover_image_url !== null) {
            $document['cover_image_url'] = (string) $row->cover_image_url;
        }

        if ($row->logo_url !== null) {
            $document['logo_url'] = (string) $row->logo_url;
        }

        if ($row->cover_image_blurhash !== null) {
            $document['cover_image_blurhash'] = (string) $row->cover_image_blurhash;
        }

        if ($row->logo_blurhash !== null) {
            $document['logo_blurhash'] = (string) $row->logo_blurhash;
        }

        if ($row->latitude !== null && $row->longitude !== null) {
            $document['location'] = [(float) $row->latitude, (float) $row->longitude];
        }

        return $document;
    }
}
