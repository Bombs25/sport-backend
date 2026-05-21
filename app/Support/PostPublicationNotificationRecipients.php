<?php

namespace App\Support;

use App\Models\User;
use App\Services\Post\FetchPostService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PostPublicationNotificationRecipients
{
    /**
     * @return list<int>
     */
    public static function userIdsFor(
        int $publicationId,
        string $publicationType,
        int $excludeUserId,
    ): array {
        if ($publicationType === FetchPostService::PUBLICATION_TYPE_REGULAR) {
            $authorId = DB::table('posts')
                ->where('id', $publicationId)
                ->value('user_id');

            if ($authorId === null) {
                return [];
            }

            $authorId = (int) $authorId;

            if ($authorId === $excludeUserId) {
                return [];
            }

            return [$authorId];
        }

        return self::matchTeamMemberIdsFor($publicationId, $excludeUserId);
    }

    /**
     * @return Collection<int, User>
     */
    public static function usersFor(
        int $publicationId,
        string $publicationType,
        int $excludeUserId,
    ): Collection {
        $userIds = self::userIdsFor($publicationId, $publicationType, $excludeUserId);

        if ($userIds === []) {
            return new Collection;
        }

        return User::findMany($userIds);
    }

    /**
     * @return list<int>
     */
    private static function matchTeamMemberIdsFor(int $matchResultId, int $excludeUserId): array
    {
        $teamIds = DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->where('match_results.id', $matchResultId)
            ->select(['match_events.home_team_id', 'match_events.away_team_id'])
            ->first();

        if ($teamIds === null) {
            return [];
        }

        return DB::table('team_members')
            ->whereIn('team_id', [$teamIds->home_team_id, $teamIds->away_team_id])
            ->where('status', 'active')
            ->where('user_id', '!=', $excludeUserId)
            ->distinct()
            ->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
