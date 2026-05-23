<?php

namespace App\Services\Account;

use App\Support\PublicImageUrl;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il fait : gère la relation de blocage entre utilisateurs (`user_blocks`).
 *
 * Pourquoi : centraliser la logique métier — quand A bloque B on supprime les
 * `follows` réciproques en transaction (cohérent avec le schéma §1.6 et la
 * notice projet « pas de double endpoint »).
 */
class UserBlockService
{
    /**
     * Bloque `$blockedUserId` pour `$blockerUserId`. Idempotent (re-bloquer ne
     * fait rien). En cas de blocage frais, supprime les `follows` dans les
     * deux sens pour ne plus voir le contenu de l'autre.
     *
     * @return bool `true` si un nouveau blocage a été créé, `false` si déjà bloqué.
     */
    public function block(int $blockerUserId, int $blockedUserId): bool
    {
        if ($blockerUserId === $blockedUserId) {
            return false;
        }

        return DB::transaction(function () use ($blockerUserId, $blockedUserId): bool {
            $alreadyBlocked = DB::table('user_blocks')
                ->where('blocker_id', $blockerUserId)
                ->where('blocked_id', $blockedUserId)
                ->exists();

            if ($alreadyBlocked) {
                return false;
            }

            DB::table('user_blocks')->insert([
                'blocker_id' => $blockerUserId,
                'blocked_id' => $blockedUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('follows')
                ->where(function ($q) use ($blockerUserId, $blockedUserId): void {
                    $q->where('follower_id', $blockerUserId)->where('following_id', $blockedUserId);
                })
                ->orWhere(function ($q) use ($blockerUserId, $blockedUserId): void {
                    $q->where('follower_id', $blockedUserId)->where('following_id', $blockerUserId);
                })
                ->delete();

            return true;
        });
    }

    /**
     * Débloque (suppression idempotente de la ligne `user_blocks`).
     */
    public function unblock(int $blockerUserId, int $blockedUserId): bool
    {
        $deleted = DB::table('user_blocks')
            ->where('blocker_id', $blockerUserId)
            ->where('blocked_id', $blockedUserId)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Liste paginée cursor (par `user_blocks.id` desc) des utilisateurs bloqués
     * par `$blockerUserId`. Renvoie un payload prêt pour l'API.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listPaginated(int $blockerUserId, int $limit, ?string $cursor): array
    {
        $limit = max(1, min(50, $limit));

        $query = DB::table('user_blocks')
            ->join('users', 'users.id', '=', 'user_blocks.blocked_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('user_blocks.blocker_id', $blockerUserId)
            ->select([
                'user_blocks.id as block_id',
                'users.id as user_id',
                'users.name',
                'user_profiles.handle',
                'user_profiles.display_name',
                'user_profiles.avatar_url',
                'user_blocks.created_at as blocked_at',
            ])
            ->orderByDesc('user_blocks.id');

        if ($cursor !== null && $cursor !== '' && ctype_digit($cursor)) {
            $query->where('user_blocks.id', '<', (int) $cursor);
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $items = $rows->take($limit);

        return [
            'data' => $items->map(static fn (object $row): array => [
                'id' => (int) $row->user_id,
                'name' => $row->name,
                'handle' => $row->handle,
                'display_name' => $row->display_name,
                'avatar_url' => PublicImageUrl::from($row->avatar_url),
                'blocked_at' => $row->blocked_at,
            ])->values()->all(),
            'meta' => [
                'next_cursor' => $hasMore ? (string) $items->last()->block_id : null,
                'has_more' => $hasMore,
                'per_page' => $limit,
            ],
        ];
    }

    /**
     * Helper utilisé par d'autres surfaces (feed, follow, etc.) pour vérifier
     * si A a bloqué B ou inversement.
     */
    public function isBlockedEitherWay(int $userA, int $userB): bool
    {
        return DB::table('user_blocks')
            ->where(function ($q) use ($userA, $userB): void {
                $q->where('blocker_id', $userA)->where('blocked_id', $userB);
            })
            ->orWhere(function ($q) use ($userA, $userB): void {
                $q->where('blocker_id', $userB)->where('blocked_id', $userA);
            })
            ->exists();
    }
}
