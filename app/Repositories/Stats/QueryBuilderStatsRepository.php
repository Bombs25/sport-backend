<?php

namespace App\Repositories\Stats;

use App\Contracts\Stats\StatsRepository;
use App\Services\Stats\SeasonWindow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueryBuilderStatsRepository implements StatsRepository
{
    public function incrementTeamStats(
        int $teamId,
        int $sportId,
        SeasonWindow $seasonWindow,
        string $counterColumn,
        int $pointDelta,
        mixed $now,
    ): void {
        $allowedCounters = ['victory_count', 'draw_count', 'defeat_count'];
        if (! in_array($counterColumn, $allowedCounters, true)) {
            throw ValidationException::withMessages([
                'counter_column' => __('Compteur de statistiques invalide.'),
            ]);
        }

        $seasonStartAt = $seasonWindow->startDate->startOfDay()->toDateTimeString();
        $seasonEndAt = $seasonWindow->endDate->endOfDay()->toDateTimeString();

        $statsId = DB::table('stats')
            ->where('team_id', $teamId)
            ->where('sport_id', $sportId)
            ->whereBetween('created_at', [$seasonStartAt, $seasonEndAt])
            ->lockForUpdate()
            ->value('id');

        if ($statsId === null) {
            $statsId = DB::table('stats')->insertGetId([
                'team_id' => $teamId,
                'sport_id' => $sportId,
                'victory_count' => 0,
                'draw_count' => 0,
                'defeat_count' => 0,
                'point_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('stats')
            ->where('id', (int) $statsId)
            ->update([
                $counterColumn => DB::raw($counterColumn.' + 1'),
                'point_count' => DB::raw('point_count + '.(int) $pointDelta),
                'updated_at' => $now,
            ]);
    }

    public function loadTeamSnapshots(int $sportId, SeasonWindow $seasonWindow, array $teamIds): array
    {
        $rows = DB::query()
            ->fromSub(
                DB::table('stats')
                    ->join('teams', 'teams.id', '=', 'stats.team_id')
                    ->where('stats.sport_id', $sportId)
                    ->whereBetween('stats.created_at', [
                        $seasonWindow->startDate->startOfDay()->toDateTimeString(),
                        $seasonWindow->endDate->endOfDay()->toDateTimeString(),
                    ])
                    ->selectRaw('
                        teams.id as team_id,
                        teams.name as team_name,
                        stats.point_count as point_count,
                        RANK() OVER (ORDER BY stats.point_count DESC) as rank_position
                    '),
                'ranked_stats'
            )
            ->whereIn('team_id', $teamIds)
            ->select([
                'team_id',
                'team_name',
                'point_count',
                'rank_position',
            ])
            ->get()
            ->keyBy(fn (object $row): int => (int) $row->team_id);

        $snapshots = [];
        foreach ($teamIds as $teamId) {
            $snapshot = $rows->get($teamId);
            $snapshots[$teamId] = [
                'team_id' => $teamId,
                'team_name' => (string) ($snapshot->team_name ?? ''),
                'point_count' => (int) ($snapshot->point_count ?? 0),
                'rank' => isset($snapshot->rank_position) ? (int) $snapshot->rank_position : null,
            ];
        }

        return $snapshots;
    }

    public function maxPointCount(int $sportId, SeasonWindow $seasonWindow): int
    {
        return (int) DB::table('stats')
            ->where('sport_id', $sportId)
            ->whereBetween('created_at', [
                $seasonWindow->startDate->startOfDay()->toDateTimeString(),
                $seasonWindow->endDate->endOfDay()->toDateTimeString(),
            ])
            ->max('point_count');
    }

    public function loadSportRanking(
        int $sportId,
        SeasonWindow $seasonWindow,
        int $page = 1,
        int $perPage = 10,
    ): array {
        $safePage = max(1, $page);
        $safePerPage = max(1, $perPage);
        $offset = ($safePage - 1) * $safePerPage;

        $rows = DB::query()
            ->fromSub(
                DB::table('stats')
                    ->join('teams', 'teams.id', '=', 'stats.team_id')
                    ->where('stats.sport_id', $sportId)
                    ->whereBetween('stats.created_at', [
                        $seasonWindow->startDate->startOfDay()->toDateTimeString(),
                        $seasonWindow->endDate->endOfDay()->toDateTimeString(),
                    ])
                    ->selectRaw('
                        teams.id as team_id,
                        teams.name as team_name,
                        teams.logo_url as team_logo_url,
                        stats.victory_count as victory_count,
                        stats.draw_count as draw_count,
                        stats.defeat_count as defeat_count,
                        stats.point_count as point_count,
                        RANK() OVER (ORDER BY stats.point_count DESC) as rank_position
                    '),
                'ranked_stats'
            )
            ->orderBy('rank_position')
            ->orderBy('team_name')
            ->offset($offset)
            ->limit($safePerPage)
            ->get();

        return $rows
            ->map(static fn (object $row): array => [
                'rank' => (int) $row->rank_position,
                'team_id' => (int) $row->team_id,
                'team_name' => (string) $row->team_name,
                'logo_url' => $row->team_logo_url !== null ? (string) $row->team_logo_url : null,
                'victory_count' => (int) $row->victory_count,
                'draw_count' => (int) $row->draw_count,
                'defeat_count' => (int) $row->defeat_count,
                'point_count' => (int) $row->point_count,
            ])
            ->values()
            ->all();
    }

    public function loadAvailableRankingYears(int $sportId): array
    {
        return DB::table('stats')
            ->where('sport_id', $sportId)
            ->selectRaw('YEAR(created_at) as year_value')
            ->distinct()
            ->orderByDesc('year_value')
            ->pluck('year_value')
            ->map(static fn ($year): int => (int) $year)
            ->values()
            ->all();
    }
}
