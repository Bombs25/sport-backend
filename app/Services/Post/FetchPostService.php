<?php

namespace App\Services\Post;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FetchPostService
{
    public const PUBLICATION_TYPE_MATCH_RESULT = 'match_result';

    /**
     * Types stockés dans la table `post_likes` pour un like sur un `match_result` (aligné sur `post_type` API).
     *
     * @var list<string>
     */
    public const MATCH_RESULT_LIKE_PUBLICATION_TYPES = ['regular', 'automatic'];

    /**
     * @param  array<int, int>  $clientViewedMatchResultIds  Identifiants déjà vus côté client (ex. MMKV) ; si vide, lecture cache puis `user_post_views`.
     * @return Collection<int, object>
     */
    public function fetchMatchResultFeed(int $viewerUserId, array $clientViewedMatchResultIds, int $limit): Collection
    {
        $viewedIds = $this->resolveViewedMatchResultIds($viewerUserId, $clientViewedMatchResultIds);

        $followingIds = DB::table('follows')
            ->where('follower_id', $viewerUserId)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($followingIds === []) {
            return collect();
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
            ->orderByRaw('COALESCE(match_results.validated_at, match_results.submitted_at, match_results.created_at) DESC')
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
                'viewer_match_likes.publication_id as liker_id',
            ])
            ->addSelect(DB::raw('viewer_match_likes.publication_id IS NOT NULL AS viewer_has_liked'));

        if ($viewedIds !== []) {
            $query->whereNotIn('match_results.id', $viewedIds);
        }

        return $query->get()->map(function (object $row): object {
            $row->viewer_has_liked = (bool) ($row->viewer_has_liked ?? false);

            return $row;
        });
    }

    /**
     * @param  array<int, int>  $clientViewedMatchResultIds
     * @return array<int, int>
     */
    public function resolveViewedMatchResultIds(int $viewerUserId, array $clientViewedMatchResultIds): array
    {
        $normalized = array_values(array_unique(array_map(
            static fn (int|string $id): int => (int) $id,
            $clientViewedMatchResultIds,
        )));

        if ($normalized !== []) {
            return $normalized;
        }

        return Cache::rememberForever($this->viewedMatchResultsCacheKey($viewerUserId), function () use ($viewerUserId): array {
            return DB::table('user_post_views')
                ->where('user_id', $viewerUserId)
                ->where('publication_type', self::PUBLICATION_TYPE_MATCH_RESULT)
                ->pluck('publication_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        });
    }

    private function viewedMatchResultsCacheKey(int $userId): string
    {
        return 'user_post_views:match_results:'.$userId;
    }
}
