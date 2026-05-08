<?php

namespace Database\Seeders;

use App\Contracts\Stats\SeasonStrategy;
use App\Services\Stats\SeasonWindow;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatsFromMatchResultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('stats')
            || ! DB::getSchemaBuilder()->hasTable('match_results')
            || ! DB::getSchemaBuilder()->hasTable('match_events')
            || ! DB::getSchemaBuilder()->hasTable('teams')
            || ! DB::getSchemaBuilder()->hasTable('sports')) {
            return;
        }

        $seasonStrategy = app(SeasonStrategy::class);
        $rowsByKey = [];

        DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('sports', 'sports.id', '=', 'home_teams.sport_id')
            ->where('match_results.status', 'validated')
            ->select([
                'match_results.id as chunk_id',
                'match_results.home_score',
                'match_results.away_score',
                'match_results.validated_at',
                'match_results.responded_at',
                'match_results.updated_at',
                'match_results.created_at',
                'match_events.home_team_id',
                'match_events.away_team_id',
                'home_teams.sport_id',
                'sports.slug',
            ])
            ->orderBy('match_results.id')
            ->chunkById(1000, function ($results) use (&$rowsByKey, $seasonStrategy): void {
                foreach ($results as $result) {
                    $playedAt = CarbonImmutable::parse(
                        $result->validated_at
                        ?? $result->responded_at
                        ?? $result->updated_at
                        ?? $result->created_at
                    );

                    $seasonWindow = $seasonStrategy->resolveWindowForDate($playedAt);
                    $sportSlug = (string) $result->slug;
                    $homeTeamId = (int) $result->home_team_id;
                    $awayTeamId = (int) $result->away_team_id;
                    $sportId = (int) $result->sport_id;
                    $homeScore = (int) $result->home_score;
                    $awayScore = (int) $result->away_score;

                    $homeStats = $this->statsDeltaForMatch($sportSlug, $homeScore, $awayScore, true);
                    $awayStats = $this->statsDeltaForMatch($sportSlug, $homeScore, $awayScore, false);

                    $this->accumulateRow($rowsByKey, $homeTeamId, $sportId, $seasonWindow, $homeStats);
                    $this->accumulateRow($rowsByKey, $awayTeamId, $sportId, $seasonWindow, $awayStats);
                }
            }, 'match_results.id', 'chunk_id');

        if ($rowsByKey === []) {
            return;
        }

        $now = now();
        $rows = collect($rowsByKey)
            ->values()
            ->map(static fn (array $row): array => [
                'team_id' => $row['team_id'],
                'sport_id' => $row['sport_id'],
                'victory_count' => $row['victory_count'],
                'draw_count' => $row['draw_count'],
                'defeat_count' => $row['defeat_count'],
                'point_count' => $row['point_count'],
                'created_at' => $row['created_at'],
                'updated_at' => $now,
            ])
            ->all();

        DB::table('stats')->upsert(
            $rows,
            ['team_id', 'sport_id', 'created_at'],
            ['victory_count', 'draw_count', 'defeat_count', 'point_count', 'updated_at']
        );
    }

    /**
     * @return array{victory_count: int, draw_count: int, defeat_count: int, point_count: int}
     */
    private function statsDeltaForMatch(string $sportSlug, int $homeScore, int $awayScore, bool $forHomeTeam): array
    {
        $teamScore = $forHomeTeam ? $homeScore : $awayScore;
        $opponentScore = $forHomeTeam ? $awayScore : $homeScore;
        $drawPoints = in_array($sportSlug, ['football', 'basketball'], true) ? 1 : 0;

        if ($teamScore > $opponentScore) {
            return [
                'victory_count' => 1,
                'draw_count' => 0,
                'defeat_count' => 0,
                'point_count' => 3,
            ];
        }

        if ($teamScore === $opponentScore) {
            return [
                'victory_count' => 0,
                'draw_count' => 1,
                'defeat_count' => 0,
                'point_count' => $drawPoints,
            ];
        }

        return [
            'victory_count' => 0,
            'draw_count' => 0,
            'defeat_count' => 1,
            'point_count' => 0,
        ];
    }

    /**
     * @param  array<string, array{team_id: int, sport_id: int, created_at: string, victory_count: int, draw_count: int, defeat_count: int, point_count: int}>  $rowsByKey
     * @param  array{victory_count: int, draw_count: int, defeat_count: int, point_count: int}  $delta
     */
    private function accumulateRow(array &$rowsByKey, int $teamId, int $sportId, SeasonWindow $seasonWindow, array $delta): void
    {
        $seasonStartAt = $seasonWindow->startDate->startOfDay()->toDateTimeString();
        $rowKey = $teamId.'-'.$sportId.'-'.$seasonStartAt;
        if (! isset($rowsByKey[$rowKey])) {
            $rowsByKey[$rowKey] = [
                'team_id' => $teamId,
                'sport_id' => $sportId,
                'created_at' => $seasonStartAt,
                'victory_count' => 0,
                'draw_count' => 0,
                'defeat_count' => 0,
                'point_count' => 0,
            ];
        }

        $rowsByKey[$rowKey]['victory_count'] += $delta['victory_count'];
        $rowsByKey[$rowKey]['draw_count'] += $delta['draw_count'];
        $rowsByKey[$rowKey]['defeat_count'] += $delta['defeat_count'];
        $rowsByKey[$rowKey]['point_count'] += $delta['point_count'];
    }
}
