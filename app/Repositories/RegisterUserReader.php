<?php

namespace App\Repositories;

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
            ->select([
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
                'user_profiles.latitude',
                'user_profiles.longitude',
                'user_profiles.city',
                'user_profiles.address_line',
                'user_profiles.birth_date',
            ])
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
}
