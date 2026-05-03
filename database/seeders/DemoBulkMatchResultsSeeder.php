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
 */
class DemoBulkMatchResultsSeeder extends Seeder
{
    private const TARGET_MIN = 200;

    private const EVENT_NOTES = '__bulk_match_results__';

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
        $now = now();
        $created = 0;

        DB::transaction(function () use ($need, $teamIds, $captainByTeam, $now, &$created): void {
            $n = count($teamIds);

            for ($i = 0; $i < $need; $i++) {
                $homeTeamId = $teamIds[$i % $n];
                $offset = 1 + ($i % max(1, $n - 1));
                $awayTeamId = $teamIds[($i + $offset) % $n];

                $homeCap = $captainByTeam[$homeTeamId] ?? null;
                $awayCap = $captainByTeam[$awayTeamId] ?? null;
                if ($homeCap === null || $awayCap === null) {
                    continue;
                }

                $scheduledAt = $now->copy()->subDays(($i % 400) + 1)->subHours($i % 24);
                $bucket = $i % 10;
                if ($bucket < 7) {
                    $eventId = $this->insertBulkMatchEvent(
                        $homeTeamId,
                        $awayTeamId,
                        'finished',
                        $scheduledAt,
                        $now,
                        $i,
                    );
                    $this->insertValidatedBundle(
                        $eventId,
                        $homeTeamId,
                        $awayTeamId,
                        $homeCap,
                        $awayCap,
                        $i,
                        $now,
                    );
                } elseif ($bucket < 9) {
                    $eventId = $this->insertBulkMatchEvent(
                        $homeTeamId,
                        $awayTeamId,
                        'scheduled',
                        $scheduledAt,
                        $now,
                        $i,
                    );
                    $this->insertPendingBundle(
                        $eventId,
                        $homeTeamId,
                        $awayTeamId,
                        $homeCap,
                        $i,
                        $now,
                    );
                } else {
                    $eventId = $this->insertBulkMatchEvent(
                        $homeTeamId,
                        $awayTeamId,
                        'scheduled',
                        $scheduledAt,
                        $now,
                        $i,
                    );
                    $this->insertRefusedBundle(
                        $eventId,
                        $homeTeamId,
                        $awayTeamId,
                        $homeCap,
                        $awayCap,
                        $i,
                        $now,
                    );
                }

                $created++;
            }
        });

        if ($this->command !== null) {
            $total = (int) DB::table('match_results')->count();
            $this->command->info('DemoBulkMatchResultsSeeder : +'.$created.' résultat(s) ; total match_results = '.$total.'.');
        }
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

    private function insertBulkMatchEvent(
        int $homeTeamId,
        int $awayTeamId,
        string $status,
        CarbonInterface $scheduledAt,
        CarbonInterface $now,
        int $index,
    ): int {
        return (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAt,
            'venue' => 'Terrain bulk démo #'.($index + 1),
            'status' => $status,
            'notes' => self::EVENT_NOTES,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertValidatedBundle(
        int $matchEventId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        int $awayCaptainId,
        int $seed,
        CarbonInterface $now,
    ): void {
        $hs = $seed % 6;
        $as = ($seed * 2) % 6;
        $resultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => $hs,
            'away_score' => $as,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $homeCaptainId,
            'submitted_at' => $now->copy()->subHours(8 + ($seed % 12)),
            'responded_by_user_id' => $awayCaptainId,
            'responded_at' => $now->copy()->subHours(4 + ($seed % 6)),
            'validated_at' => $now->copy()->subHours(1 + ($seed % 3)),
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('match_opponent_evaluations')->insert([
            [
                'match_result_id' => $resultId,
                'evaluator_team_id' => $homeTeamId,
                'evaluator_user_id' => $homeCaptainId,
                'evaluated_team_id' => $awayTeamId,
                'fair_play_rating' => 3 + ($seed % 3),
                'punctuality_rating' => 3 + (($seed + 1) % 3),
                'remarks' => 'Bulk démo — domicile.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'match_result_id' => $resultId,
                'evaluator_team_id' => $awayTeamId,
                'evaluator_user_id' => $awayCaptainId,
                'evaluated_team_id' => $homeTeamId,
                'fair_play_rating' => 3 + (($seed + 2) % 3),
                'punctuality_rating' => 3 + (($seed + 3) % 3),
                'remarks' => 'Bulk démo — extérieur.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function insertPendingBundle(
        int $matchEventId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        int $seed,
        CarbonInterface $now,
    ): void {
        $resultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => $seed % 4,
            'away_score' => ($seed * 2) % 4,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $homeCaptainId,
            'submitted_at' => $now->copy()->subHours(2 + ($seed % 8)),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('match_opponent_evaluations')->insert([
            'match_result_id' => $resultId,
            'evaluator_team_id' => $homeTeamId,
            'evaluator_user_id' => $homeCaptainId,
            'evaluated_team_id' => $awayTeamId,
            'fair_play_rating' => 4,
            'punctuality_rating' => 4,
            'remarks' => 'Bulk démo — en attente validation.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertRefusedBundle(
        int $matchEventId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        int $awayCaptainId,
        int $seed,
        CarbonInterface $now,
    ): void {
        $resultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 3,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'refused',
            'submitted_by_user_id' => $homeCaptainId,
            'submitted_at' => $now->copy()->subDays(3 + ($seed % 5)),
            'responded_by_user_id' => $awayCaptainId,
            'responded_at' => $now->copy()->subDays(2 + ($seed % 3)),
            'validated_at' => null,
            'refusal_reason' => 'Bulk démo — score contesté.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('match_opponent_evaluations')->insert([
            'match_result_id' => $resultId,
            'evaluator_team_id' => $homeTeamId,
            'evaluator_user_id' => $homeCaptainId,
            'evaluated_team_id' => $awayTeamId,
            'fair_play_rating' => 3,
            'punctuality_rating' => 3,
            'remarks' => 'Bulk démo — avant refus.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
