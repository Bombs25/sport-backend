<?php

namespace Database\Seeders;

use App\Services\Team\MatchResultService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Complète {@see match_results} jusqu’à au moins {@see self::TARGET_MIN} lignes.
 *
 * Cohérence avec {@see MatchResultService} :
 * - {@see match_results.status} = `score_pending_validation` ou `refused` ⇒ {@see match_events.status} = `scheduled`
 * - {@see match_results.status} = `validated` ⇒ {@see match_events.status} = `finished`
 *
 * À exécuter après les seeders qui créent équipes et membres. Idempotent par comptage : ne rajoute que le manquant.
 *
 * Les insertions se font par lots ({@see self::CHUNK_SIZE}) pour rester raisonnables en temps et en allers-retours SQL.
 * Sous MySQL/InnoDB, un INSERT multi-lignes dans une transaction attribue des {@see match_events.id} et
 * {@see match_results.id} consécutifs ; le seeder suppose l’absence d’insertions concurrentes sur ces tables pendant l’exécution.
 */
class DemoBulkMatchResultsSeeder extends Seeder
{
    private const TARGET_MIN = 500_000;

    /**
     * Nombre de match_events (donc de match_results) insérés par transaction.
     */
    private const CHUNK_SIZE = 500;

    private const EVENT_NOTES = '__bulk_match_results__';

    private const PROGRESS_EVERY = 50_000;

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('match_results')) {
            return;
        }

        $current = (int) DB::table('match_results')->count();
        $need = max(0, self::TARGET_MIN - $current);

        if ($need === 0) {
            if ($this->command !== null) {
                $this->command->info('DemoBulkMatchResultsSeeder : déjà '.$current.' résultat(s) (≥ '.self::TARGET_MIN.'), rien à ajouter.');
            }

            return;
        }

        $teamIds = DB::table('teams')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        if (count($teamIds) < 2) {
            if ($this->command !== null) {
                $this->command->warn('DemoBulkMatchResultsSeeder : au moins 2 équipes requises, ignoré.');
            }

            return;
        }

        $captainByTeam = $this->resolveCaptainsByTeam($teamIds);
        $teamIds = array_values(array_filter(
            $teamIds,
            fn (int $id): bool => isset($captainByTeam[$id]),
        ));

        if (count($teamIds) < 2) {
            if ($this->command !== null) {
                $this->command->warn('DemoBulkMatchResultsSeeder : au moins 2 équipes avec capitaine (ou membre actif) requises, ignoré.');
            }

            return;
        }

        $now = now();
        $created = 0;
        $variantBase = 0;

        while ($created < $need) {
            $take = min(self::CHUNK_SIZE, $need - $created);

            DB::transaction(function () use ($take, $teamIds, $captainByTeam, $now, $variantBase): void {
                $this->insertBulkChunk($teamIds, $captainByTeam, $now, $variantBase, $take);
            });

            $created += $take;
            $variantBase += $take;

            if ($this->command !== null) {
                if ($created === $need) {
                    $this->command->info('DemoBulkMatchResultsSeeder : '.$created.' / '.$need.' terminé pour cette exécution.');
                } elseif ($created % self::PROGRESS_EVERY === 0) {
                    $this->command->info('DemoBulkMatchResultsSeeder : '.$created.' / '.$need.' créés dans cette exécution…');
                }
            }
        }

        if ($this->command !== null) {
            $total = (int) DB::table('match_results')->count();
            $this->command->info('DemoBulkMatchResultsSeeder : +'.$created.' résultat(s) ; total match_results = '.$total.'.');
        }
    }

    /**
     * @param  array<int, int>  $teamIds  équipes disposant d’au moins un capitaine ou membre actif résolu
     * @param  array<int, int>  $captainByTeam  user_id par team_id
     */
    private function insertBulkChunk(
        array $teamIds,
        array $captainByTeam,
        CarbonInterface $now,
        int $variantBase,
        int $take,
    ): void {
        $n = count($teamIds);
        $eventRows = [];
        $resultRows = [];
        $evaluationRows = [];

        for ($k = 0; $k < $take; $k++) {
            $i = $variantBase + $k;
            $homeTeamId = $teamIds[$i % $n];
            $offset = 1 + ($i % max(1, $n - 1));
            $awayTeamId = $teamIds[($i + $offset) % $n];
            $homeCap = $captainByTeam[$homeTeamId];
            $awayCap = $captainByTeam[$awayTeamId];

            // UTC : évite les heures « inexistantes » (passage heure d’été) si APP_TIMEZONE est DST.
            $scheduledAt = $now->copy()->utc()->subDays(($i % 400) + 1)->subHours($i % 24);
            $bucket = $i % 10;

            if ($bucket < 7) {
                $eventRows[] = [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'scheduled_at' => $scheduledAt,
                    'venue' => 'Terrain bulk démo #'.($i + 1),
                    'status' => 'finished',
                    'notes' => self::EVENT_NOTES,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } else {
                $eventRows[] = [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'scheduled_at' => $scheduledAt,
                    'venue' => 'Terrain bulk démo #'.($i + 1),
                    'status' => 'scheduled',
                    'notes' => self::EVENT_NOTES,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('match_events')->insert($eventRows);
        $firstEventId = (int) DB::connection()->getPdo()->lastInsertId();
        $eventIdForIndex = static fn (int $k): int => $firstEventId + $k;

        for ($k = 0; $k < $take; $k++) {
            $i = $variantBase + $k;
            $homeTeamId = $teamIds[$i % $n];
            $offset = 1 + ($i % max(1, $n - 1));
            $awayTeamId = $teamIds[($i + $offset) % $n];
            $homeCap = $captainByTeam[$homeTeamId];
            $awayCap = $captainByTeam[$awayTeamId];
            $bucket = $i % 10;
            $eventId = $eventIdForIndex($k);

            if ($bucket < 7) {
                $hs = $i % 6;
                $as = ($i * 2) % 6;
                $resultRows[] = [
                    'match_event_id' => $eventId,
                    'home_score' => $hs,
                    'away_score' => $as,
                    'total_comments' => 0,
                    'total_likes' => 0,
                    'status' => 'validated',
                    'submitted_by_user_id' => $homeCap,
                    'submitted_at' => $now->copy()->subHours(8 + ($i % 12)),
                    'responded_by_user_id' => $awayCap,
                    'responded_at' => $now->copy()->subHours(4 + ($i % 6)),
                    'validated_at' => $now->copy()->subHours(1 + ($i % 3)),
                    'refusal_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } elseif ($bucket < 9) {
                $resultRows[] = [
                    'match_event_id' => $eventId,
                    'home_score' => $i % 4,
                    'away_score' => ($i * 2) % 4,
                    'total_comments' => 0,
                    'total_likes' => 0,
                    'status' => 'score_pending_validation',
                    'submitted_by_user_id' => $homeCap,
                    'submitted_at' => $now->copy()->subHours(2 + ($i % 8)),
                    'responded_by_user_id' => null,
                    'responded_at' => null,
                    'validated_at' => null,
                    'refusal_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } else {
                $resultRows[] = [
                    'match_event_id' => $eventId,
                    'home_score' => 1,
                    'away_score' => 3,
                    'total_comments' => 0,
                    'total_likes' => 0,
                    'status' => 'refused',
                    'submitted_by_user_id' => $homeCap,
                    'submitted_at' => $now->copy()->subDays(3 + ($i % 5)),
                    'responded_by_user_id' => $awayCap,
                    'responded_at' => $now->copy()->subDays(2 + ($i % 3)),
                    'validated_at' => null,
                    'refusal_reason' => 'Bulk démo — score contesté.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('match_results')->insert($resultRows);
        $firstResultId = (int) DB::connection()->getPdo()->lastInsertId();

        for ($k = 0; $k < $take; $k++) {
            $i = $variantBase + $k;
            $homeTeamId = $teamIds[$i % $n];
            $offset = 1 + ($i % max(1, $n - 1));
            $awayTeamId = $teamIds[($i + $offset) % $n];
            $homeCap = $captainByTeam[$homeTeamId];
            $awayCap = $captainByTeam[$awayTeamId];
            $bucket = $i % 10;
            $resultId = $firstResultId + $k;

            if ($bucket < 7) {
                $evaluationRows[] = [
                    'match_result_id' => $resultId,
                    'evaluator_team_id' => $homeTeamId,
                    'evaluator_user_id' => $homeCap,
                    'evaluated_team_id' => $awayTeamId,
                    'fair_play_rating' => 3 + ($i % 3),
                    'punctuality_rating' => 3 + (($i + 1) % 3),
                    'remarks' => 'Bulk démo — domicile.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $evaluationRows[] = [
                    'match_result_id' => $resultId,
                    'evaluator_team_id' => $awayTeamId,
                    'evaluator_user_id' => $awayCap,
                    'evaluated_team_id' => $homeTeamId,
                    'fair_play_rating' => 3 + (($i + 2) % 3),
                    'punctuality_rating' => 3 + (($i + 3) % 3),
                    'remarks' => 'Bulk démo — extérieur.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } elseif ($bucket < 9) {
                $evaluationRows[] = [
                    'match_result_id' => $resultId,
                    'evaluator_team_id' => $homeTeamId,
                    'evaluator_user_id' => $homeCap,
                    'evaluated_team_id' => $awayTeamId,
                    'fair_play_rating' => 4,
                    'punctuality_rating' => 4,
                    'remarks' => 'Bulk démo — en attente validation.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } else {
                $evaluationRows[] = [
                    'match_result_id' => $resultId,
                    'evaluator_team_id' => $homeTeamId,
                    'evaluator_user_id' => $homeCap,
                    'evaluated_team_id' => $awayTeamId,
                    'fair_play_rating' => 3,
                    'punctuality_rating' => 3,
                    'remarks' => 'Bulk démo — avant refus.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('match_opponent_evaluations')->insert($evaluationRows);
    }

    /**
     * @param  array<int, int>  $teamIds
     * @return array<int, int>
     */
    private function resolveCaptainsByTeam(array $teamIds): array
    {
        $map = [];
        foreach ($teamIds as $teamId) {
            $uid = DB::table('team_members')
                ->where('team_id', $teamId)
                ->where('role', 'captain')
                ->where('status', 'active')
                ->value('user_id');

            if ($uid === null) {
                $uid = DB::table('team_members')
                    ->where('team_id', $teamId)
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->value('user_id');
            }

            if ($uid !== null) {
                $map[$teamId] = (int) $uid;
            }
        }

        return $map;
    }
}
