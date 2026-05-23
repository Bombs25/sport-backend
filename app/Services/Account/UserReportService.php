<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il fait : enregistre un signalement utilisateur (`user_reports`).
 *
 * Pourquoi : extraire la logique du contrôleur (≤ 100 lignes) et garantir
 * l'unicité d'un signalement **actif** par couple (reporter, reported).
 * Un nouveau signalement n'est créé que si le précédent est `resolved` /
 * `dismissed` — sinon on renvoie l'existant tel quel.
 */
class UserReportService
{
    /**
     * @return array{report_id: int, created: bool}
     */
    public function report(int $reporterId, int $reportedUserId, string $reason, ?string $details): array
    {
        return DB::transaction(function () use ($reporterId, $reportedUserId, $reason, $details): array {
            $existing = DB::table('user_reports')
                ->where('reporter_id', $reporterId)
                ->where('reported_user_id', $reportedUserId)
                ->whereIn('status', ['pending', 'under_review'])
                ->first();

            if ($existing !== null) {
                return [
                    'report_id' => (int) $existing->id,
                    'created' => false,
                ];
            }

            $id = DB::table('user_reports')->insertGetId([
                'reporter_id' => $reporterId,
                'reported_user_id' => $reportedUserId,
                'reason' => $reason,
                'details' => $details,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'report_id' => (int) $id,
                'created' => true,
            ];
        });
    }
}
