<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class TeamNotificationRecipients
{
    /**
     * Capitaines et membres actifs d'une équipe, plus le créateur s'il n'est pas déjà membre.
     *
     * @return array<int, int>
     */
    public static function activeMembersAndCreator(int $teamId, ?int $excludeUserId = null): array
    {
        $query = DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('status', 'active')
            ->whereIn('role', ['captain', 'member']);

        if ($excludeUserId !== null) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        $recipientIds = $query
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $creatorId = DB::table('teams')->where('id', $teamId)->value('creator_id');
        if ($creatorId === null) {
            return array_values(array_unique($recipientIds));
        }

        $creatorId = (int) $creatorId;
        if ($excludeUserId !== $creatorId && ! in_array($creatorId, $recipientIds, true)) {
            $recipientIds[] = $creatorId;
        }

        return array_values(array_unique($recipientIds));
    }
}
