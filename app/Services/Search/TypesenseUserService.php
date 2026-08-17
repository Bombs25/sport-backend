<?php

namespace App\Services\Search;

use App\Services\Messages\MessageableUserFilterService;
use App\Support\PublicImageUrl;
use App\Support\Search\TypesenseSyncGuard;
use App\Support\Search\TypesenseUsersCollectionSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

use Http\Client\Curl\Client as CurlClient;
use Http\Discovery\Psr17FactoryDiscovery;

/**
 * Ce qu'il fait : gère la collection Typesense `users` (création et import de documents).
 *
 * Pourquoi : centralise la logique de synchronisation des utilisateurs vers Typesense ;
 * utilisé par les seeders et les futurs observers/jobs de synchronisation.
 */
class TypesenseUserService
{
    private Client $client;

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
     * Crée la collection `users` si elle n'existe pas encore.
     *
     * @throws TypesenseClientError
     */
    public function ensureCollection(): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $this->client->collections['users']->retrieve();
        } catch (ObjectNotFound) {
            $this->client->collections->create(TypesenseUsersCollectionSchema::definition());
        }
    }

    /**
     * Recrée la collection `users` pour garantir que le schéma Typesense suit le code avant un seed.
     *
     * @throws TypesenseClientError
     */
    public function recreateCollection(): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $this->client->collections['users']->delete();
        } catch (ObjectNotFound) {
            //
        }

        $this->client->collections->create(TypesenseUsersCollectionSchema::definition());
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
        if ($documents === [] || ! TypesenseSyncGuard::isEnabled()) {
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
            // "location:({$lat}, {$lng}, {$radius} km)",
        ];

        if ($excludeUserId !== null) {
            $filters[] = 'id:!=' . $excludeUserId;
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
     * Recherche tous les profils publics (sans géoloc) — utilisé par le
     * picker « partager à » où ce qui compte est le nom/handle, pas la
     * distance. Le filtre `who_can_message_me` est appliqué ailleurs
     * ({@see MessageableUserFilterService}) parce
     * que Typesense ne stocke pas ce champ.
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     *
     * @throws TypesenseClientError
     */
    public function searchPublicUsersForDm(
        string $query = '*',
        int $page = 1,
        int $perPage = 20,
        ?int $excludeUserId = null,
    ): array {
        $q = trim($query) !== '' ? trim($query) : '*';
        $filters = ['is_private:=false'];

        if ($excludeUserId !== null) {
            $filters[] = 'id:!=' . $excludeUserId;
        }

        $response = $this->client->collections['users']->documents->search([
            'q' => $q,
            'query_by' => 'name,display_name,handle,bio,city',
            'filter_by' => implode(' && ', $filters),
            'sort_by' => '_text_match:desc',
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
            ],
        ];
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
