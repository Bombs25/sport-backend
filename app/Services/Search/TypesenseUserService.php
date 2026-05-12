<?php

namespace App\Services\Search;

use App\Support\PublicImageUrl;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

/**
 * Ce qu'il fait : gère la collection Typesense `users` (création et import de documents).
 *
 * Pourquoi : centralise la logique de synchronisation des utilisateurs vers Typesense ;
 * utilisé par les seeders et les futurs observers/jobs de synchronisation.
 */
class TypesenseUserService
{
    private Client $client;

    /** @var array{name: string, fields: list<array<string, mixed>>, default_sorting_field: string} */
    private const SCHEMA = [
        'name' => 'users',
        'fields' => [
            ['name' => 'id', 'type' => 'int64'],
            ['name' => 'name', 'type' => 'string'],
            ['name' => 'email', 'type' => 'string', 'index' => false],
            ['name' => 'display_name', 'type' => 'string'],
            ['name' => 'handle', 'type' => 'string'],
            ['name' => 'bio', 'type' => 'string', 'optional' => true],
            ['name' => 'avatar_url', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'avatar_blurhash', 'type' => 'string', 'optional' => true, 'index' => false],
            ['name' => 'is_private', 'type' => 'bool', 'facet' => true],
            ['name' => 'city', 'type' => 'string', 'facet' => true, 'optional' => true],
            ['name' => 'location', 'type' => 'geopoint', 'optional' => true],
            ['name' => 'created_at', 'type' => 'int64'],
        ],
        'default_sorting_field' => 'created_at',
    ];

    public function __construct()
    {
        $t = config('services.typesense');

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
        ]);
    }

    /**
     * Crée la collection `users` si elle n'existe pas encore.
     *
     * @throws TypesenseClientError
     */
    public function ensureCollection(): void
    {
        try {
            $this->client->collections['users']->retrieve();
        } catch (ObjectNotFound) {
            $this->client->collections->create(self::SCHEMA);
        }
    }

    /**
     * Recrée la collection `users` pour garantir que le schéma Typesense suit le code avant un seed.
     *
     * @throws TypesenseClientError
     */
    public function recreateCollection(): void
    {
        try {
            $this->client->collections['users']->delete();
        } catch (ObjectNotFound) {
            //
        }

        $this->client->collections->create(self::SCHEMA);
    }

    /**
     * Importe un lot de documents dans la collection `users` (mode upsert).
     *
     * @param  list<array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     display_name: string,
     *     handle: string,
     *     bio?: string,
     *     avatar_url?: string,
     *     avatar_blurhash?: string,
     *     is_private: bool,
     *     city: string|null,
     *     location?: array{float, float},
     *     created_at: int,
     * }>  $documents
     *
     * @throws TypesenseClientError
     */
    public function importDocuments(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $doc = $this->client->collections['users']->documents->import(
            $documents,
            ['action' => 'upsert']
        );
        Log::info('Import Typesense users démarré.', [
            'count' => count($documents),
            'documents' => $doc,
        ]);
    }

    /**
     * Synchronise un utilisateur depuis MySQL vers Typesense.
     *
     * @throws TypesenseClientError
     */
    public function syncUserFromDatabase(int $userId): void
    {
        $document = $this->documentForUser($userId);

        if ($document === null) {
            return;
        }

        $this->importDocuments([$document]);
    }

    /**
     * Resynchronise tous les utilisateurs depuis MySQL vers Typesense.
     *
     * @throws TypesenseClientError
     */
    public function syncAllUsersFromDatabase(int $chunkSize = 1000): int
    {
        $this->ensureCollection();

        $synced = 0;

        $this->userDocumentQuery()
            ->orderBy('users.id')
            ->chunkById($chunkSize, function ($rows) use (&$synced): void {
                $documents = [];

                foreach ($rows as $row) {
                    $documents[] = $this->documentFromDatabaseRow($row);
                }

                $this->importDocuments($documents);
                $synced += count($documents);
            }, 'users.id', 'id');

        return $synced;
    }

    /**
     * Recherche des profils publics autour d'une coordonnée WGS-84.
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     *
     * @throws TypesenseClientError
     */
    public function searchPublicUsersAround(
        float $latitude,
        float $longitude,
        string $query = '*',
        float $radiusKm = 100.0,
        int $page = 1,
        int $perPage = 10,
        ?int $excludeUserId = null,
    ): array {
        $q = trim($query) !== '' ? trim($query) : '*';
        $lat = $this->formatGeoNumber($latitude);
        $lng = $this->formatGeoNumber($longitude);
        $radius = $this->formatGeoNumber($radiusKm);
        $filters = [
            'is_private:=false',
            "location:({$lat}, {$lng}, {$radius} km)",
        ];

        if ($excludeUserId !== null) {
            $filters[] = 'id:!='.$excludeUserId;
        }

        $response = $this->client->collections['users']->documents->search([
            'q' => $q,
            'query_by' => 'name,display_name,handle,bio,city',
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

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     display_name: string,
     *     handle: string,
     *     bio?: string,
     *     avatar_url?: string,
     *     avatar_blurhash?: string,
     *     is_private: bool,
     *     city?: string,
     *     location?: array{float, float},
     *     created_at: int,
     * }|null
     */
    private function documentForUser(int $userId): ?array
    {
        $row = $this->userDocumentQuery()
            ->where('users.id', $userId)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->documentFromDatabaseRow($row);
    }

    private function userDocumentQuery(): Builder
    {
        return DB::table('users')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.created_at',
                'user_profiles.display_name',
                'user_profiles.handle',
                'user_profiles.bio',
                'user_profiles.avatar_url',
                'user_profiles.avatar_blurhash',
                'user_profiles.is_private',
                'user_profiles.city',
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
                'name' => (string) ($document['name'] ?? ''),
                'display_name' => (string) ($document['display_name'] ?? ''),
                'handle' => (string) ($document['handle'] ?? ''),
                'bio' => $document['bio'] ?? null,
                'avatar_url' => PublicImageUrl::from($document['avatar_url'] ?? null),
                'avatar_blurhash' => $document['avatar_blurhash'] ?? null,
                'is_private' => (bool) ($document['is_private'] ?? false),
                'city' => $document['city'] ?? null,
                'latitude' => $location !== null && array_key_exists(0, $location) ? (float) $location[0] : null,
                'longitude' => $location !== null && array_key_exists(1, $location) ? (float) $location[1] : null,
                'distance_meters' => is_numeric($meters) ? (int) $meters : null,
                'distance_km' => is_numeric($meters) ? round(((float) $meters) / 1000, 1) : null,
            ];
        }

        return $results;
    }

    private function formatGeoNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     display_name: string,
     *     handle: string,
     *     bio?: string,
     *     avatar_url?: string,
     *     avatar_blurhash?: string,
     *     is_private: bool,
     *     city?: string,
     *     location?: array{float, float},
     *     created_at: int,
     * }
     */
    private function documentFromDatabaseRow(stdClass $row): array
    {
        $document = [
            'id' => (string) $row->id,
            'name' => (string) $row->name,
            'email' => (string) $row->email,
            'display_name' => (string) $row->display_name,
            'handle' => (string) $row->handle,
            'is_private' => (bool) $row->is_private,
            'created_at' => strtotime((string) $row->created_at) ?: now()->timestamp,
        ];

        if ($row->bio !== null) {
            $document['bio'] = (string) $row->bio;
        }

        if ($row->avatar_url !== null) {
            $document['avatar_url'] = (string) $row->avatar_url;
        }

        if ($row->avatar_blurhash !== null) {
            $document['avatar_blurhash'] = (string) $row->avatar_blurhash;
        }

        if ($row->city !== null) {
            $document['city'] = (string) $row->city;
        }

        if ($row->latitude !== null && $row->longitude !== null) {
            $document['location'] = [(float) $row->latitude, (float) $row->longitude];
        }

        return $document;
    }
}
