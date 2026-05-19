<?php

namespace App\Services\Follow;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowService
{
    /**
     * Crée ou met à jour une relation de suivi.
     * Compte privé cible → `pending` ; sinon `accepted`.
     *
     * @return array{status: 'pending'|'accepted', notify: bool, follow_id: int|null}
     */
    public function follow(int $followerId, int $followingId): array
    {
        $existing = DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->first();

        if ($existing !== null && $existing->status === 'accepted') {
            return [
                'status' => 'accepted',
                'notify' => false,
                'follow_id' => (int) $existing->id,
            ];
        }

        $isPrivate = (bool) DB::table('user_profiles')
            ->where('user_id', $followingId)
            ->value('is_private');

        $status = $isPrivate ? 'pending' : 'accepted';
        $previousStatus = $existing !== null ? (string) $existing->status : null;
        $notify = $existing === null || $previousStatus !== $status;

        DB::table('follows')->upsert([
            [
                'follower_id' => $followerId,
                'following_id' => $followingId,
                'status' => $status,
                'created_at' => $existing?->created_at ?? now(),
                'updated_at' => now(),
            ],
        ], ['follower_id', 'following_id'], ['status', 'updated_at']);

        if ($status === 'accepted') {
            $this->addFollowingToCache($followerId, $followingId);
        }

        $followId = DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->value('id');

        return [
            'status' => $status,
            'notify' => $notify,
            'follow_id' => $followId !== null ? (int) $followId : null,
        ];
    }

    public function unfollow(int $followerId, int $followingId): void
    {
        DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->delete();

        $cacheKey = $this->followingCacheKey($followerId);
        $cachedFollowingIds = Cache::store('app_main_cache')->get($cacheKey, []);
        $normalizedFollowingIds = collect(is_array($cachedFollowingIds) ? $cachedFollowingIds : [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0 && $id !== $followingId)
            ->unique()
            ->values()
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $normalizedFollowingIds);
    }

    public function pendingIncomingCount(int $userId): int
    {
        return (int) DB::table('follows')
            ->where('following_id', $userId)
            ->where('status', 'pending')
            ->count();
    }

    /**
     * @return array{items: Collection<int, object>, next_cursor: string|null, has_more: bool}
     */
    public function listPendingIncomingPaginated(int $userId, int $limit, ?string $cursor): array
    {
        $cursorFollowId = $this->decodeCursor($cursor);

        $query = DB::table('follows')
            ->join('users', 'users.id', '=', 'follows.follower_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('follows.following_id', $userId)
            ->where('follows.status', 'pending')
            ->when($cursorFollowId !== null, fn ($q) => $q->where('follows.id', '<', $cursorFollowId))
            ->orderByDesc('follows.id');

        $rows = $query
            ->limit($limit + 1)
            ->select([
                'follows.id as follow_row_id',
                'users.id',
                'users.name',
                'users.email',
                'user_profiles.handle',
                'user_profiles.display_name',
                'user_profiles.avatar_url',
                'follows.created_at as requested_at',
            ])
            ->get();

        $hasMore = $rows->count() > $limit;
        if ($hasMore) {
            $rows->pop();
        }

        $nextCursor = null;
        $last = $rows->last();
        if ($hasMore && $last !== null) {
            $nextCursor = $this->encodeCursor((int) $last->follow_row_id);
        }

        return [
            'items' => $rows,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * @return array{notify: bool, follower_id: int, follow_id: int}
     */
    public function acceptIncomingRequest(int $followingUserId, int $followRowId): array
    {
        $row = DB::table('follows')
            ->where('id', $followRowId)
            ->where('following_id', $followingUserId)
            ->where('status', 'pending')
            ->first();

        if ($row === null) {
            throw ValidationException::withMessages([
                'follow_request_id' => [__('Demande de suivi introuvable.')],
            ]);
        }

        DB::table('follows')
            ->where('id', $followRowId)
            ->update([
                'status' => 'accepted',
                'updated_at' => now(),
            ]);

        $followerId = (int) $row->follower_id;
        $this->addFollowingToCache($followerId, $followingUserId);

        return [
            'notify' => true,
            'follower_id' => $followerId,
            'follow_id' => $followRowId,
        ];
    }

    public function rejectIncomingRequest(int $followingUserId, int $followRowId): void
    {
        $deleted = DB::table('follows')
            ->where('id', $followRowId)
            ->where('following_id', $followingUserId)
            ->where('status', 'pending')
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'follow_request_id' => [__('Demande de suivi introuvable.')],
            ]);
        }
    }

    /**
     * Stats affichées sur le profil public (posts publiés + follows acceptés).
     *
     * @return array{posts_count: int, followers_count: int, following_count: int}
     */
    public function profileStatsForUser(int $userId): array
    {
        $counts = $this->countsForUser($userId);

        $postsCount = (int) DB::table('posts')
            ->where('user_id', $userId)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->count();

        return [
            'posts_count' => $postsCount,
            'followers_count' => $counts['followers_count'],
            'following_count' => $counts['following_count'],
        ];
    }

    /**
     * Comptes des relations acceptées pour l'utilisateur (followers = qui suit cet utilisateur).
     *
     * @return array{followers_count: int, following_count: int, pending_requests_count: int}
     */
    public function countsForUser(int $userId): array
    {
        $followersCount = (int) DB::table('follows')
            ->where('following_id', $userId)
            ->where('status', 'accepted')
            ->count();

        $followingCount = (int) DB::table('follows')
            ->where('follower_id', $userId)
            ->where('status', 'accepted')
            ->count();

        return [
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
            'pending_requests_count' => $this->pendingIncomingCount($userId),
        ];
    }

    /**
     * IDs des utilisateurs que le viewer suit en **accepted**, parmi les IDs cibles (pour enrichir une liste sans N+1).
     *
     * @param  array<int, int>  $targetUserIds
     * @return array<int, int>
     */
    public function acceptedFollowingTargetIdsAmong(int $viewerId, array $targetUserIds): array
    {
        if ($targetUserIds === []) {
            return [];
        }

        $uniqueIds = array_values(array_unique(array_map(
            static fn (int|string $id): int => (int) $id,
            $targetUserIds,
        )));

        return DB::table('follows')
            ->where('follower_id', $viewerId)
            ->where('status', 'accepted')
            ->whereIn('following_id', $uniqueIds)
            ->pluck('following_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array{items: Collection<int, object>, next_cursor: string|null, has_more: bool}
     */
    public function listForUserPaginated(int $userId, string $type, int $limit, ?string $cursor): array
    {
        $cursorFollowId = $this->decodeCursor($cursor);

        if ($type === 'followers') {
            $query = DB::table('follows')
                ->join('users', 'users.id', '=', 'follows.follower_id')
                ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->where('follows.following_id', $userId)
                ->where('follows.status', 'accepted')
                ->when($cursorFollowId !== null, fn ($q) => $q->where('follows.id', '<', $cursorFollowId))
                ->orderByDesc('follows.id');
        } else {
            $query = DB::table('follows')
                ->join('users', 'users.id', '=', 'follows.following_id')
                ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->where('follows.follower_id', $userId)
                ->where('follows.status', 'accepted')
                ->when($cursorFollowId !== null, fn ($q) => $q->where('follows.id', '<', $cursorFollowId))
                ->orderByDesc('follows.id');
        }

        $rows = $query
            ->limit($limit + 1)
            ->select([
                'follows.id as follow_row_id',
                'users.id',
                'users.name',
                'users.email',
                'user_profiles.handle',
                'user_profiles.display_name',
                'user_profiles.avatar_url',
                'follows.created_at as followed_at',
            ])
            ->get();

        $hasMore = $rows->count() > $limit;
        if ($hasMore) {
            $rows->pop();
        }

        $nextCursor = null;
        $last = $rows->last();
        if ($hasMore && $last !== null) {
            $nextCursor = $this->encodeCursor((int) $last->follow_row_id);
        }

        return [
            'items' => $rows,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    private function encodeCursor(int $followRowId): string
    {
        $json = json_encode(['fid' => $followRowId], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private function decodeCursor(?string $cursor): ?int
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }

        $padded = strtr($cursor, '-_', '+/');
        $padLen = (4 - (strlen($padded) % 4)) % 4;
        $padded .= str_repeat('=', $padLen);

        $decoded = base64_decode($padded, true);
        if ($decoded === false) {
            throw ValidationException::withMessages([
                'cursor' => [__('Le curseur de pagination est invalide.')],
            ]);
        }

        try {
            /** @var array{fid?: int} $payload */
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'cursor' => [__('Le curseur de pagination est invalide.')],
            ]);
        }

        if (! isset($payload['fid']) || ! is_int($payload['fid'])) {
            throw ValidationException::withMessages([
                'cursor' => [__('Le curseur de pagination est invalide.')],
            ]);
        }

        return $payload['fid'];
    }

    private function addFollowingToCache(int $followerId, int $followingId): void
    {
        $cacheKey = $this->followingCacheKey($followerId);
        $cachedFollowingIds = Cache::store('app_main_cache')->get($cacheKey, []);
        $normalizedFollowingIds = collect(is_array($cachedFollowingIds) ? $cachedFollowingIds : [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->push($followingId)
            ->unique()
            ->values()
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $normalizedFollowingIds);
    }

    private function followingCacheKey(int $followerId): string
    {
        return 'follow:following_ids:'.$followerId;
    }
}
