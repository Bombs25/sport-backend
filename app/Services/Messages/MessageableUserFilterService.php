<?php

namespace App\Services\Messages;

use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il fait : filtre une liste de candidats destinataires selon le
 * `who_can_message_me` du profil de chacun (vu depuis `$viewerUserId`).
 *
 * Pourquoi : Typesense ne stocke pas ce champ ; appliquer un check
 * unitaire par user serait N+1. On fait deux requêtes en batch :
 *  1) `user_profiles` → récupérer `who_can_message_me` pour tous les
 *     candidats en un coup ;
 *  2) `follows` → savoir qui le viewer suit (pour la règle `followers`).
 *
 * Convention §1.7 : Query Builder explicite, pas d'Eloquent relations.
 */
class MessageableUserFilterService
{
    /**
     * @param  list<int>  $candidateUserIds  IDs à filtrer (ordre conservé pour les survivants).
     * @return list<int> Sous-ensemble autorisé à recevoir un DM du viewer.
     */
    public function filterDmAllowed(int $viewerUserId, array $candidateUserIds): array
    {
        if ($candidateUserIds === []) {
            return [];
        }

        $audiences = DB::table('user_profiles')
            ->whereIn('user_id', $candidateUserIds)
            ->pluck('who_can_message_me', 'user_id');

        $viewerFollowsIds = DB::table('follows')
            ->where('follower_id', $viewerUserId)
            ->whereIn('following_id', $candidateUserIds)
            ->where('status', 'accepted')
            ->pluck('following_id')
            ->all();

        $followsSet = array_flip(array_map('intval', $viewerFollowsIds));
        $allowed = [];

        foreach ($candidateUserIds as $userId) {
            $audience = (string) ($audiences[$userId] ?? 'everyone');
            $isAllowed = match ($audience) {
                'everyone' => true,
                'followers' => isset($followsSet[$userId]),
                default => false, // 'nobody' ou inconnu → bloqué
            };

            if ($isAllowed) {
                $allowed[] = $userId;
            }
        }

        return $allowed;
    }
}
