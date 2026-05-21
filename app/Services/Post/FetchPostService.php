<?php

namespace App\Services\Post;

use App\Support\PublicImageUrl;
use App\Support\UserProfileLocation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FetchPostService
{
    public const PUBLICATION_TYPE_REGULAR = 'regular';

    public const PUBLICATION_TYPE_MATCH_RESULT = 'match_result';

    /**
     * Types stockés dans la table `post_likes` pour un like sur un `match_result` (aligné sur `post_type` API).
     *
     * @var list<string>
     */
    public const MATCH_RESULT_LIKE_PUBLICATION_TYPES = ['regular', 'automatic'];

    public const MATCH_RESULT_FEED_STATUS = 'validated';

    public const CENTRE_INTERET_TAG = 'centre_interet';

    /** Rayon « proches » pour la strate centre d'intérêt (mètres). */
    public const CENTRE_INTERET_RADIUS_METERS = 20_000;

    public const MATCH_RESULTS_LIMIT = 100;

    public const REGULAR_POST_FEED_STATUS = 'published';

    public const REGULAR_POST_TARGET = 10;

    /**
     * @param  array<int, int>  $clientViewedMatchResultIds  Identifiants déjà vus côté client (ex. MMKV) ; si vide, lecture cache puis `user_post_views`.
     * @return array{items: Collection<int, object>, count: int}
     */
    public function fetchMatchResultFeed(int $viewerUserId, array $clientViewedMatchResultIds, int $limit): array
    {
        $viewedIds = $this->resolveViewedMatchResultIds($viewerUserId, $clientViewedMatchResultIds);

        $followingIds = $this->resolveFollowingIds($viewerUserId);

        if ($followingIds === []) {
            return [
                'items' => collect(),
                'count' => 0,
            ];
        }

        $viewerLikesSub = DB::table('post_likes')
            ->select('publication_id')
            ->where('users_id', $viewerUserId)
            ->whereIn('publication_type', self::MATCH_RESULT_LIKE_PUBLICATION_TYPES);

        $query = DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->leftJoinSub($viewerLikesSub, 'viewer_match_likes', function ($join): void {
                $join->on('viewer_match_likes.publication_id', '=', 'match_results.id');
            })
            ->whereIn('match_results.submitted_by_user_id', $followingIds)
            ->where('match_results.status', self::MATCH_RESULT_FEED_STATUS)
            ->orderByDesc('match_results.validated_at')
            ->orderByDesc('match_results.id')
            ->limit($limit)
            ->select([
                'match_results.id',
                'match_results.match_event_id',
                'match_results.status',
                'match_results.home_score',
                'match_results.away_score',
                'match_results.total_comments',
                'match_results.total_likes',
                'match_results.submitted_by_user_id',
                'match_results.submitted_at',
                'match_results.validated_at',
                'match_results.created_at',
                'match_results.updated_at',
                'match_events.scheduled_at',
                'match_events.venue',
                'match_events.status as match_event_status',
                'home_teams.id as home_team_id',
                'home_teams.name as home_team_name',
                'home_teams.logo_url as home_team_logo_url',
                'away_teams.id as away_team_id',
                'away_teams.name as away_team_name',
                'away_teams.logo_url as away_team_logo_url',
                DB::raw("'amis' AS tag"),
            ])
            ->addSelect(DB::raw('viewer_match_likes.publication_id IS NOT NULL AS viewer_has_liked'));

        if ($viewedIds !== []) {
            $query->whereNotIn('match_results.id', $viewedIds);
        }

        $items = $query->get()->map(function (object $row): object {
            $row->viewer_has_liked = (bool) ($row->viewer_has_liked ?? false);
            $row->home_team_logo_url = PublicImageUrl::from($row->home_team_logo_url);
            $row->away_team_logo_url = PublicImageUrl::from($row->away_team_logo_url);

            return $row;
        });

        return [
            'items' => $items,
            'count' => $items->count(),
        ];
    }

    /**
     * Strate « centre d’intérêt » : `match_results` validés, auteurs **non** suivis (pas de ligne `follows` accepted),
     * à ≤ 20 km du viewer (POINT `user_profiles`), au moins un sport en commun (`user_sports`).
     * Chaque ligne inclut `distance_km` (km) : haversine sphère 6371 km entre les lat/lng du viewer (déjà en PHP)
     * et celles de `author_profiles.location`. Métier : entrée dans ce filtre seulement si `location` auteur non NULL
     * (et viewer géolocalisé en amont) ; pas de distance « vide » côté résultat.
     * Sous-requête initiale : les **500** derniers `match_results` validés que le viewer **n’a pas vus**, dont l’auteur
     * **partage au moins un sport** avec lui et **n’est pas** dans ses suivis acceptés ; puis jointures, proximité, etc.
     * Tri : distance croissante (plus proches d’abord), puis `match_results.id` décroissant.
     * La fusion avec {@see fetchMatchResultFeed} est assurée par {@see fetchMatchResultFeedUnionWithCentreInteret}.
     *
     * @param  array<int, int>  $clientViewedMatchResultIds
     * @return array{items: Collection<int, object>, count: int}
     */
    public function fetchMatchResultCentreInteretFeed(int $viewerUserId, array $clientViewedMatchResultIds, int $limit): array
    {
        $viewerCoords = UserProfileLocation::currentLatLngForUser($viewerUserId);
        if ($viewerCoords['latitude'] === null || $viewerCoords['longitude'] === null) {
            return [
                'items' => collect(),
                'count' => 0,
            ];
        }

        $viewerLat = (float) $viewerCoords['latitude'];
        $viewerLon = (float) $viewerCoords['longitude'];
        if (! is_finite($viewerLat) || ! is_finite($viewerLon)) {
            return [
                'items' => collect(),
                'count' => 0,
            ];
        }

        $viewedIds = $this->resolveViewedMatchResultIds($viewerUserId, $clientViewedMatchResultIds);
        $cap = min(max($limit, 1), 100);

        $viewerSportIds = $this->resolveViewerSportIds($viewerUserId);

        if ($viewerSportIds === []) {
            return [
                'items' => collect(),
                'count' => 0,
            ];
        }

        $followingIds = $this->resolveFollowingIds($viewerUserId);

        $viewerLikesSub = DB::table('post_likes')
            ->select('publication_id')
            ->where('users_id', $viewerUserId)
            ->whereIn('publication_type', self::MATCH_RESULT_LIKE_PUBLICATION_TYPES);

        $query = DB::table(function ($sub) use ($viewerUserId, $viewedIds, $viewerSportIds, $followingIds): void {
            $sub->from('match_results')
                ->select('match_results.*')
                ->join('user_profiles as author_profiles', 'author_profiles.user_id', '=', 'match_results.submitted_by_user_id')
                ->where('match_results.status', self::MATCH_RESULT_FEED_STATUS)
                ->where('match_results.submitted_by_user_id', '<>', $viewerUserId)
                ->whereNotNull('author_profiles.location')
                ->whereExists(function ($exists) use ($viewerSportIds): void {
                    $exists->from('user_sports')
                        ->whereColumn('user_sports.user_id', 'match_results.submitted_by_user_id')
                        ->whereIn('user_sports.sport_id', $viewerSportIds);
                });
            // ->whereRaw(
            //     '(' . $this->centreInteretDistanceMetersSql($viewerLat, $viewerLon) . ') <= ?',
            //     [self::CENTRE_INTERET_RADIUS_METERS],
            // );

            if ($followingIds !== []) {
                $sub->whereNotIn('match_results.submitted_by_user_id', $followingIds);
            }

            if ($viewedIds !== []) {
                $sub->whereNotIn('match_results.id', $viewedIds);
            }

            $sub->orderBy('match_results.validated_at', 'desc')
                ->orderByDesc('match_results.id')
                ->limit(self::MATCH_RESULTS_LIMIT);
        }, 'match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->join('user_profiles as author_profiles', 'author_profiles.user_id', '=', 'match_results.submitted_by_user_id')
            ->leftJoinSub($viewerLikesSub, 'viewer_match_likes', function ($join): void {
                $join->on('viewer_match_likes.publication_id', '=', 'match_results.id');
            })
            ->orderBy('match_results.validated_at', 'DESC');

        $items = $query
            ->select([
                'match_results.id',
                'match_results.match_event_id',
                'match_results.status',
                'match_results.home_score',
                'match_results.away_score',
                'match_results.total_comments',
                'match_results.total_likes',
                'match_results.submitted_by_user_id',
                'match_results.submitted_at',
                'match_results.validated_at',
                'match_results.created_at',
                'match_results.updated_at',
                'match_events.scheduled_at',
                'match_events.venue',
                'match_events.status as match_event_status',
                'home_teams.id as home_team_id',
                'home_teams.name as home_team_name',
                'home_teams.logo_url as home_team_logo_url',
                'away_teams.id as away_team_id',
                'away_teams.name as away_team_name',
                'away_teams.logo_url as away_team_logo_url',
                DB::raw('(('.$this->centreInteretDistanceMetersSql($viewerLat, $viewerLon).') / 1000) AS distance_km'),
                DB::raw("'".self::CENTRE_INTERET_TAG."' AS tag"),
                DB::raw('viewer_match_likes.publication_id IS NOT NULL AS viewer_has_liked'),
            ])
            ->limit($cap)

            ->orderBy('distance_km', 'ASC')
            ->get()
            ->map(function (object $row): object {
                $row->viewer_has_liked = (bool) ($row->viewer_has_liked ?? false);
                $row->distance_km = (int) round((float) $row->distance_km);
                $row->home_team_logo_url = PublicImageUrl::from($row->home_team_logo_url);
                $row->away_team_logo_url = PublicImageUrl::from($row->away_team_logo_url);

                return $row;
            });

        return [
            'items' => $items,
            'count' => $items->count(),
        ];
    }

    /**
     * Variante centre d'intérêt : sans filtre de sport viewer, en excluant les IDs déjà présents
     * dans {@see fetchMatchResultCentreInteretFeed}.
     *
     * @param  array<int, int>  $clientViewedMatchResultIds
     * @return array{items: Collection<int, object>, count: int}
     */
    public function fetchMatchResultBaseOnDistanceOnly(int $viewerUserId, array $clientViewedMatchResultIds, int $limit): array
    {
        $viewerCoords = UserProfileLocation::currentLatLngForUser($viewerUserId);
        if ($viewerCoords['latitude'] === null || $viewerCoords['longitude'] === null) {
            return [
                'items' => collect(),
                'count' => 0,
            ];
        }

        $viewerLat = (float) $viewerCoords['latitude'];
        $viewerLon = (float) $viewerCoords['longitude'];
        if (! is_finite($viewerLat) || ! is_finite($viewerLon)) {
            return [
                'items' => collect(),
                'count' => 0,
            ];
        }

        $viewedIds = $this->resolveViewedMatchResultIds($viewerUserId, $clientViewedMatchResultIds);
        $cap = min(max($limit, 1), 100);
        $followingIds = $this->resolveFollowingIds($viewerUserId);

        $amisFeedIds = $this->fetchMatchResultFeed($viewerUserId, $clientViewedMatchResultIds, 10)['items']
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $centreInteretIds = $this->fetchMatchResultCentreInteretFeed($viewerUserId, $clientViewedMatchResultIds, 10)['items']
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $viewerLikesSub = DB::table('post_likes')
            ->select('publication_id')
            ->where('users_id', $viewerUserId)
            ->whereIn('publication_type', self::MATCH_RESULT_LIKE_PUBLICATION_TYPES);

        $query = DB::table(function ($sub) use ($viewerUserId, $viewedIds, $followingIds, $amisFeedIds, $centreInteretIds): void {
            $sub->from('match_results')
                ->select('match_results.*')
                ->join('user_profiles as author_profiles', 'author_profiles.user_id', '=', 'match_results.submitted_by_user_id')
                ->where('match_results.status', self::MATCH_RESULT_FEED_STATUS)
                ->where('match_results.submitted_by_user_id', '<>', $viewerUserId)
                ->whereNotNull('author_profiles.location');

            if ($followingIds !== []) {
                $sub->whereNotIn('match_results.submitted_by_user_id', $followingIds);
            }

            if ($viewedIds !== []) {
                $sub->whereNotIn('match_results.id', $viewedIds);
            }

            if ($amisFeedIds !== []) {
                $sub->whereNotIn('match_results.id', $amisFeedIds);
            }

            if ($centreInteretIds !== []) {
                $sub->whereNotIn('match_results.id', $centreInteretIds);
            }

            $sub->orderBy('match_results.validated_at', 'desc')
                ->orderByDesc('match_results.id')
                ->limit(self::MATCH_RESULTS_LIMIT);
        }, 'match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->join('user_profiles as author_profiles', 'author_profiles.user_id', '=', 'match_results.submitted_by_user_id')
            ->leftJoinSub($viewerLikesSub, 'viewer_match_likes', function ($join): void {
                $join->on('viewer_match_likes.publication_id', '=', 'match_results.id');
            })
            ->orderBy('match_results.validated_at', 'DESC');

        $items = $query
            ->select([
                'match_results.id',
                'match_results.match_event_id',
                'match_results.status',
                'match_results.home_score',
                'match_results.away_score',
                'match_results.total_comments',
                'match_results.total_likes',
                'match_results.submitted_by_user_id',
                'match_results.submitted_at',
                'match_results.validated_at',
                'match_results.created_at',
                'match_results.updated_at',
                'match_events.scheduled_at',
                'match_events.venue',
                'match_events.status as match_event_status',
                'home_teams.id as home_team_id',
                'home_teams.name as home_team_name',
                'home_teams.logo_url as home_team_logo_url',
                'away_teams.id as away_team_id',
                'away_teams.name as away_team_name',
                'away_teams.logo_url as away_team_logo_url',
                DB::raw('(('.$this->centreInteretDistanceMetersSql($viewerLat, $viewerLon).') / 1000) AS distance_km'),
                DB::raw("'base_on_distance' AS tag"),
                DB::raw('viewer_match_likes.publication_id IS NOT NULL AS viewer_has_liked'),
            ])
            ->limit($cap)
            ->orderBy('distance_km', 'ASC')
            ->get()
            ->map(function (object $row): object {
                $row->viewer_has_liked = (bool) ($row->viewer_has_liked ?? false);
                $row->distance_km = (int) round((float) $row->distance_km);
                $row->home_team_logo_url = PublicImageUrl::from($row->home_team_logo_url);
                $row->away_team_logo_url = PublicImageUrl::from($row->away_team_logo_url);

                return $row;
            });

        return [
            'items' => $items,
            'count' => $items->count(),
        ];
    }

    /**
     * Exécute les 3 strates dans l'ordre :
     * 1) amis, 2) centre d'intérêt, 3) centre d'intérêt sans sport.
     * Objectif final: retourner au plus 10 éléments uniques.
     *
     * @param  array<int, int>  $clientViewedMatchResultIds
     * @return array{items: Collection<int, object>, count: int}
     */
    public function fetchMatchResultFeedOrdered(int $viewerUserId, array $clientViewedMatchResultIds): array
    {
        $target = 10;

        $amisFeed = $this->fetchMatchResultFeed($viewerUserId, $clientViewedMatchResultIds, $target);
        $items = $amisFeed['items']->values();
        $seenIds = $items->pluck('id')->map(static fn (mixed $id): int => (int) $id)->flip();

        if ($items->count() < $target) {
            $remaining = $target - $items->count();
            $centreFeed = $this->fetchMatchResultCentreInteretFeed($viewerUserId, $clientViewedMatchResultIds, $remaining);
            $centreDeduped = $centreFeed['items']
                ->reject(function (object $row) use ($seenIds): bool {
                    return isset($seenIds[(int) $row->id]);
                })
                ->values();

            $items = $items->concat($centreDeduped)->take($target)->values();
            $seenIds = $items->pluck('id')->map(static fn (mixed $id): int => (int) $id)->flip();
        }

        if ($items->count() < $target) {
            $remaining = $target - $items->count();
            $centreSansSportFeed = $this->fetchMatchResultBaseOnDistanceOnly($viewerUserId, $clientViewedMatchResultIds, $remaining);
            $centreSansSportDeduped = $centreSansSportFeed['items']
                ->reject(function (object $row) use ($seenIds): bool {
                    return isset($seenIds[(int) $row->id]);
                })
                ->values();

            $items = $items->concat($centreSansSportDeduped)->take($target)->values();
        }

        $this->storeViewedMatchResultIds($viewerUserId, $items);

        return [
            'items' => $items,
            'count' => $items->count(),
        ];
    }

    /**
     * Feed `Fil d'actu` séparé : posts réguliers uniquement, jamais mélangés avec `match_results`.
     *
     * @param  array<int, int>  $clientViewedRegularPostIds
     * @return array{items: Collection<int, object>, count: int}
     */
    public function fetchRegularPostFeedOrdered(int $viewerUserId, array $clientViewedRegularPostIds): array
    {
        $target = self::REGULAR_POST_TARGET;
        $viewedIds = $this->resolveViewedRegularPostIds($viewerUserId, $clientViewedRegularPostIds);

        $items = $this->fetchRegularPostsFromFollowing($viewerUserId, $viewedIds, [], $target)->values();
        $seenIds = $items->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

        if ($items->count() < $target) {
            $remaining = $target - $items->count();
            $centreItems = $this->fetchRegularPostsFromCentreInteret($viewerUserId, $viewedIds, $seenIds, $remaining);

            $items = $items->concat($centreItems)->take($target)->values();
            $seenIds = $items->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        }

        if ($items->count() < $target) {
            $remaining = $target - $items->count();
            $recentItems = $this->fetchRecentRegularPosts($viewerUserId, $viewedIds, $seenIds, $remaining);

            $items = $items->concat($recentItems)->take($target)->values();
        }

        $this->storeViewedRegularPostIds($viewerUserId, $items);

        return [
            'items' => $items,
            'count' => $items->count(),
        ];
    }

    /**
     * @param  array<int, int>  $viewedIds
     * @param  array<int, int>  $excludedPostIds
     * @return Collection<int, object>
     */
    private function fetchRegularPostsFromFollowing(int $viewerUserId, array $viewedIds, array $excludedPostIds, int $limit): Collection
    {
        $followingIds = $this->resolveFollowingIds($viewerUserId);
        if ($followingIds === []) {
            return collect();
        }

        $query = $this->regularPostBaseQuery($viewerUserId)
            ->whereIn('posts.user_id', $followingIds)
            ->whereIn('posts.visibility', ['public', 'followers'])
            ->addSelect(DB::raw("'amis' AS tag"));

        $this->applyRegularPostExclusions($query, $viewedIds, $excludedPostIds);

        return $this->attachMediaToRegularPosts(
            $query
                ->orderByDesc('posts.published_at')
                ->orderByDesc('posts.id')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * @param  array<int, int>  $viewedIds
     * @param  array<int, int>  $excludedPostIds
     * @return Collection<int, object>
     */
    private function fetchRegularPostsFromCentreInteret(int $viewerUserId, array $viewedIds, array $excludedPostIds, int $limit): Collection
    {
        $viewerSportIds = $this->resolveViewerSportIds($viewerUserId);
        if ($viewerSportIds === []) {
            return collect();
        }

        $followingIds = $this->resolveFollowingIds($viewerUserId);

        $query = $this->regularPostBaseQuery($viewerUserId)
            ->where('posts.user_id', '<>', $viewerUserId)
            ->where('posts.visibility', 'public')
            ->whereExists(function ($exists) use ($viewerSportIds): void {
                $exists->from('user_sports')
                    ->whereColumn('user_sports.user_id', 'posts.user_id')
                    ->whereIn('user_sports.sport_id', $viewerSportIds);
            })
            ->addSelect(DB::raw("'".self::CENTRE_INTERET_TAG."' AS tag"));

        if ($followingIds !== []) {
            $query->whereNotIn('posts.user_id', $followingIds);
        }

        $this->applyRegularPostExclusions($query, $viewedIds, $excludedPostIds);

        return $this->attachMediaToRegularPosts(
            $query
                ->orderByDesc('posts.published_at')
                ->orderByDesc('posts.id')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * @param  array<int, int>  $viewedIds
     * @param  array<int, int>  $excludedPostIds
     * @return Collection<int, object>
     */
    private function fetchRecentRegularPosts(int $viewerUserId, array $viewedIds, array $excludedPostIds, int $limit): Collection
    {
        $query = $this->regularPostBaseQuery($viewerUserId)
            ->where('posts.user_id', '<>', $viewerUserId)
            ->where('posts.visibility', 'public')
            ->addSelect(DB::raw("'recent' AS tag"));

        $this->applyRegularPostExclusions($query, $viewedIds, $excludedPostIds);

        return $this->attachMediaToRegularPosts(
            $query
                ->orderByDesc('posts.published_at')
                ->orderByDesc('posts.id')
                ->limit($limit)
                ->get()
        );
    }

    private function regularPostBaseQuery(int $viewerUserId): Builder
    {
        $viewerLikesSub = DB::table('post_likes')
            ->select('publication_id')
            ->where('users_id', $viewerUserId)
            ->where('publication_type', self::PUBLICATION_TYPE_REGULAR);

        return DB::table('posts')
            ->join('users as authors', 'authors.id', '=', 'posts.user_id')
            ->leftJoin('user_profiles as author_profiles', 'author_profiles.user_id', '=', 'posts.user_id')
            ->leftJoinSub($viewerLikesSub, 'viewer_regular_likes', function ($join): void {
                $join->on('viewer_regular_likes.publication_id', '=', 'posts.id');
            })
            ->where('posts.status', self::REGULAR_POST_FEED_STATUS)
            ->whereNull('posts.deleted_at')
            ->select([
                'posts.id',
                DB::raw("'".self::PUBLICATION_TYPE_REGULAR."' AS publication_type"),
                'posts.user_id',
                'posts.body',
                'posts.visibility',
                'posts.status',
                'posts.media_count',
                'posts.total_likes',
                'posts.total_comments',
                'posts.total_shares',
                'posts.published_at',
                'posts.created_at',
                'posts.updated_at',
                'authors.name as author_name',
                'author_profiles.display_name as author_display_name',
                'author_profiles.handle as author_handle',
                'author_profiles.avatar_url as author_avatar_url',
                'author_profiles.avatar_blurhash as author_avatar_blurhash',
                DB::raw('viewer_regular_likes.publication_id IS NOT NULL AS viewer_has_liked'),
            ]);
    }

    /**
     * @param  array<int, int>  $viewedIds
     * @param  array<int, int>  $excludedPostIds
     */
    private function applyRegularPostExclusions(Builder $query, array $viewedIds, array $excludedPostIds): void
    {
        $blockedPostIds = collect($viewedIds)
            ->concat($excludedPostIds)
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($blockedPostIds !== []) {
            $query->whereNotIn('posts.id', $blockedPostIds);
        }
    }

    /**
     * @param  Collection<int, object>  $items
     * @return Collection<int, object>
     */
    private function attachMediaToRegularPosts(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $postIds = $items
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $mediaByPostId = DB::table('post_media')
            ->whereIn('post_id', $postIds)
            ->orderBy('position')
            ->get()
            ->groupBy(static fn (object $media): int => (int) $media->post_id);

        return $items->map(function (object $row) use ($mediaByPostId): object {
            $row->viewer_has_liked = (bool) ($row->viewer_has_liked ?? false);
            $row->author_avatar_url = PublicImageUrl::from($row->author_avatar_url);
            $row->media_count = (int) ($row->media_count ?? 0);
            $row->total_likes = (int) ($row->total_likes ?? 0);
            $row->total_comments = (int) ($row->total_comments ?? 0);
            $row->total_shares = (int) ($row->total_shares ?? 0);
            $row->media = $mediaByPostId
                ->get((int) $row->id, collect())
                ->map(static fn (object $media): array => [
                    'id' => (int) $media->id,
                    'position' => (int) $media->position,
                    'path' => PublicImageUrl::from($media->path),
                    'blurhash' => $media->blurhash,
                    'alt_text' => $media->alt_text,
                ])
                ->values()
                ->all();

            return $row;
        });
    }

    /**
     * @deprecated Non appelée actuellement (le flux actif utilise `fetchMatchResultFeedOrdered`).
     *
     * Fil principal : jusqu’à 100 lignes {@see fetchMatchResultFeed} ; si moins de 100, union logique
     * avec {@see fetchMatchResultCentreInteretFeed} (sans doublon d’`id`, strate « amis » d’abord).
     * Le jeu fusionné est plafonné à 100, puis tronqué au `limit` demandé (max 100).
     *
     * @param  array<int, int>  $clientViewedMatchResultIds
     * @return array{items: Collection<int, object>, count: int}
     */
    // public function fetchMatchResultFeedUnionWithCentreInteret(int $viewerUserId, array $clientViewedMatchResultIds, int $limit): array
    // {
    //     $poolLimit = min(max($limit, 1), 100);

    //     $amisFeed = $this->fetchMatchResultFeed($viewerUserId, $clientViewedMatchResultIds, 100);
    //     $amisItems = $amisFeed['items'];
    //     $amisCount = $amisItems->count();

    //     if ($amisCount >= 100) {
    //         $sliced = $amisItems
    //             ->map(fn (object $row): object => $this->ensureDistanceKmOnFeedRow($row))
    //             ->take($poolLimit)
    //             ->values();

    //         $this->storeViewedMatchResultIds($viewerUserId, $sliced);

    //         return [
    //             'items' => $sliced,
    //             'count' => $sliced->count(),
    //         ];
    //     }

    //     $needed = 100 - $amisCount;
    //     $centreFeed = $this->fetchMatchResultCentreInteretFeed($viewerUserId, $clientViewedMatchResultIds, $needed);
    //     $centreItems = $centreFeed['items'];

    //     $amisIdSet = $amisItems->pluck('id')->map(static fn (mixed $id): int => (int) $id)->flip();
    //     $amisWithDistance = $amisItems->map(fn (object $row): object => $this->ensureDistanceKmOnFeedRow($row));

    //     $centreDeduped = $centreItems->reject(function (object $row) use ($amisIdSet): bool {
    //         return isset($amisIdSet[(int) $row->id]);
    //     });

    //     $union = $amisWithDistance
    //         ->concat($centreDeduped)
    //         ->take(100)
    //         ->take($poolLimit)
    //         ->values();

    //     $this->storeViewedMatchResultIds($viewerUserId, $union);

    //     return [
    //         'items' => $union,
    //         'count' => $union->count(),
    //     ];
    // }

    // private function ensureDistanceKmOnFeedRow(object $row): object
    // {
    //     if (! property_exists($row, 'distance_km')) {
    //         $row->distance_km = null;
    //     }

    //     return $row;
    // }

    /**
     * @param  array<int, int>  $clientViewedMatchResultIds
     * @return array<int, int>
     */
    public function resolveViewedMatchResultIds(int $viewerUserId, array $clientViewedMatchResultIds): array
    {
        // Union : liste envoyée par le client (session courante) + historique persistant
        // (`user_post_views`). Sans cette union, dès que le client renvoie ses IDs de
        // session le serveur oublierait la base et les match_results déjà vus
        // réapparaîtraient (2e page, session suivante, MMKV partiel...).
        return collect($clientViewedMatchResultIds)
            ->concat($this->serverSideViewedMatchResultIds($viewerUserId))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Identifiants de `match_results` déjà vus, persistés côté serveur (cache Redis, sinon `user_post_views`).
     *
     * @return array<int, int>
     */
    private function serverSideViewedMatchResultIds(int $viewerUserId): array
    {
        $cacheKey = $this->viewedMatchResultsCacheKey($viewerUserId);

        if (Cache::store('app_main_cache')->has($cacheKey)) {
            return collect(Cache::store('app_main_cache')->get($cacheKey, []))
                ->map(static fn (mixed $id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $viewedIds = DB::table('user_post_views')
            ->where('user_id', $viewerUserId)
            ->where('publication_type', self::PUBLICATION_TYPE_MATCH_RESULT)
            ->pluck('publication_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $viewedIds);

        return $viewedIds;
    }

    private function viewedMatchResultsCacheKey(int $userId): string
    {
        return 'user_post_views:match_results:'.$userId;
    }

    private function storeViewedMatchResultIds(int $viewerUserId, Collection $items): void
    {
        $viewedIds = $items
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($viewedIds === []) {
            return;
        }

        $now = now();
        $rows = array_map(
            static fn (int $id): array => [
                'user_id' => $viewerUserId,
                'publication_id' => $id,
                'publication_type' => self::PUBLICATION_TYPE_MATCH_RESULT,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $viewedIds,
        );

        DB::table('user_post_views')->upsert(
            $rows,
            ['user_id', 'publication_id', 'publication_type'],
            ['updated_at']
        );

        $cacheKey = $this->viewedMatchResultsCacheKey($viewerUserId);
        $cachedIds = collect(Cache::store('app_main_cache')->get($cacheKey, []))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->all();

        $mergedIds = collect($cachedIds)
            ->concat($viewedIds)
            ->unique()
            ->values()
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $mergedIds);
    }

    /**
     * @param  array<int, int>  $clientViewedRegularPostIds
     * @return array<int, int>
     */
    public function resolveViewedRegularPostIds(int $viewerUserId, array $clientViewedRegularPostIds): array
    {
        // Union : liste envoyée par le client (session courante) + historique persistant
        // (`user_post_views`). Sans cette union, dès que le client renvoie ses IDs de
        // session le serveur oublierait la base et les posts déjà vus réapparaîtraient
        // (2e page, session suivante, MMKV partiel...).
        return collect($clientViewedRegularPostIds)
            ->concat($this->serverSideViewedRegularPostIds($viewerUserId))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Identifiants de posts réguliers déjà vus, persistés côté serveur (cache Redis, sinon `user_post_views`).
     *
     * @return array<int, int>
     */
    private function serverSideViewedRegularPostIds(int $viewerUserId): array
    {
        $cacheKey = $this->viewedRegularPostsCacheKey($viewerUserId);

        if (Cache::store('app_main_cache')->has($cacheKey)) {
            return collect(Cache::store('app_main_cache')->get($cacheKey, []))
                ->map(static fn (mixed $id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $viewedIds = DB::table('user_post_views')
            ->where('user_id', $viewerUserId)
            ->where('publication_type', self::PUBLICATION_TYPE_REGULAR)
            ->pluck('publication_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $viewedIds);

        return $viewedIds;
    }

    private function viewedRegularPostsCacheKey(int $userId): string
    {
        return 'user_post_views:regular_posts:'.$userId;
    }

    /**
     * @param  Collection<int, object>  $items
     */
    private function storeViewedRegularPostIds(int $viewerUserId, Collection $items): void
    {
        $viewedIds = $items
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($viewedIds === []) {
            return;
        }

        $now = now();
        $rows = array_map(
            static fn (int $id): array => [
                'user_id' => $viewerUserId,
                'publication_id' => $id,
                'publication_type' => self::PUBLICATION_TYPE_REGULAR,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $viewedIds,
        );

        DB::table('user_post_views')->upsert(
            $rows,
            ['user_id', 'publication_id', 'publication_type'],
            ['updated_at']
        );

        $cacheKey = $this->viewedRegularPostsCacheKey($viewerUserId);
        $cachedIds = collect(Cache::store('app_main_cache')->get($cacheKey, []))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->all();

        $mergedIds = collect($cachedIds)
            ->concat($viewedIds)
            ->unique()
            ->values()
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $mergedIds);
    }

    /**
     * @return array<int, int>
     */
    private function resolveViewerSportIds(int $viewerUserId): array
    {
        $viewerSportsCacheKey = 'register:user:sports:'.$viewerUserId;
        if (Cache::store('app_main_cache')->has($viewerSportsCacheKey)) {
            return collect(Cache::store('app_main_cache')->get($viewerSportsCacheKey, []))
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $viewerSportIds = DB::table('user_sports')
            ->where('user_id', $viewerUserId)
            ->pluck('sport_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        Cache::store('app_main_cache')->forever($viewerSportsCacheKey, $viewerSportIds);

        return $viewerSportIds;
    }

    /**
     * @return array<int, int>
     */
    private function resolveFollowingIds(int $viewerUserId): array
    {
        $followingCacheKey = 'follow:following_ids:'.$viewerUserId;
        if (Cache::store('app_main_cache')->has($followingCacheKey)) {
            return collect(Cache::store('app_main_cache')->get($followingCacheKey, []))
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $followingIds = DB::table('follows')
            ->where('follower_id', $viewerUserId)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        Cache::store('app_main_cache')->forever($followingCacheKey, $followingIds);

        return $followingIds;
    }

    /**
     * Distance sphérique (mètres) via MySQL `ST_Distance_Sphere`, entre `author_profiles.location`
     * et le point viewer (longitude, latitude) en SRID 4326.
     */
    private function centreInteretDistanceMetersSql(float $viewerLat, float $viewerLon): string
    {
        $vlat = $this->sqlLiteralLatitudeOrLongitude($viewerLat);
        $vlon = $this->sqlLiteralLatitudeOrLongitude($viewerLon);

        return 'ST_Distance_Sphere(author_profiles.location, ST_SRID(POINT('.$vlon.', '.$vlat.'), 4326))';
    }

    private function sqlLiteralLatitudeOrLongitude(float $value): string
    {
        if ($value === 0.0) {
            return '0';
        }

        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
    }
}
