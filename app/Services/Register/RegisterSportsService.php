<?php

namespace App\Services\Register;

use Illuminate\Support\Facades\DB;

/**
 * Ce qu’il fait : remplace la liste des sports associés à l’utilisateur dans `user_sports` (synchronisation idempotente),
 * en marquant chaque sport choisi comme **favori** (`is_favorite`), aligné écran « vos favoris ».
 *
 * Pourquoi : étape onboarding maquettes (multi-sélection) ; une transaction + delete puis insert évite les doublons
 * `(user_id, sport_id)` et reflète exactement le choix envoyé par le client.
 */
class RegisterSportsService
{
    /**
     * @param  list<int>  $sportIds
     */
    public function sync(int $userId, array $sportIds): void
    {
        DB::transaction(function () use ($userId, $sportIds) {
            DB::table('user_sports')->where('user_id', $userId)->delete();

            $now = now();
            $rows = [];

            foreach (array_unique($sportIds) as $sportId) {
                $rows[] = [
                    'user_id' => $userId,
                    'sport_id' => $sportId,
                    'is_favorite' => true,
                    'skill_level' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('user_sports')->insert($rows);
            }
        });
    }
}
