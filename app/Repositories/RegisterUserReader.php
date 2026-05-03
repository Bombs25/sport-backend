<?php

namespace App\Repositories;

use App\Support\UserProfileLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu’il fait : lit en base les données utilisateur utiles au flux inscription / session (profil joint,
 * sports choisis, disponibilité d’un pseudo) via le **Query Builder** uniquement.
 *
 * Pourquoi : respect du schéma O’Sport (lectures multi-tables en SQL explicite, sans graphe Eloquent lourd)
 * pour garder des requêtes prévisibles et alignées sur les guidelines projet.
 */
class RegisterUserReader
{
    public function userWithProfile(int $userId): ?object
    {
        return DB::table('users')
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('users.id', $userId)
            ->select(array_merge([
                'users.id',
                'users.name',
                'users.email',
                'users.email_verified_at',
                'users.created_at',
                'user_profiles.display_name',
                'user_profiles.handle',
                'user_profiles.bio',
                'user_profiles.avatar_url',
                'user_profiles.is_private',
            ], UserProfileLocation::selectLatitudeLongitude('user_profiles'), [
                'user_profiles.city',
                'user_profiles.address_line',
                'user_profiles.birth_date',
            ]))
            ->first();
    }

    public function sportsForUser(int $userId): Collection
    {
        return DB::table('user_sports')
            ->join('sports', 'user_sports.sport_id', '=', 'sports.id')
            ->where('user_sports.user_id', $userId)
            ->orderBy('sports.id')
            ->select([
                'sports.id',
                'sports.name',
                'sports.slug',
                'sports.practice_type',
                'sports.avatar',
                'user_sports.is_favorite',
            ])
            ->get();
    }

    public function handleIsAvailable(string $handle, ?int $exceptUserId = null): bool
    {
        $query = DB::table('user_profiles')->where('handle', $handle);

        if ($exceptUserId !== null) {
            $query->where('user_id', '!=', $exceptUserId);
        }

        return ! $query->exists();
    }

    /**
     * Indique si une relation de suivi **acceptée** existe dans au moins un sens entre deux utilisateurs.
     */
    public function hasAnyAcceptedFollowBetween(int $userIdA, int $userIdB): bool
    {
        if ($userIdA === $userIdB) {
            return true;
        }

        return DB::table('follows')
            ->where('status', 'accepted')
            ->where(function ($query) use ($userIdA, $userIdB): void {
                $query->where(function ($q) use ($userIdA, $userIdB): void {
                    $q->where('follower_id', $userIdA)->where('following_id', $userIdB);
                })->orWhere(function ($q) use ($userIdA, $userIdB): void {
                    $q->where('follower_id', $userIdB)->where('following_id', $userIdA);
                });
            })
            ->exists();
    }

    /**
     * Le follower suit la cible avec un statut **accepted** (sens follower_id → following_id).
     */
    public function viewerFollowsTargetAccepted(int $followerId, int $followingId): bool
    {
        return DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->where('status', 'accepted')
            ->exists();
    }
}
