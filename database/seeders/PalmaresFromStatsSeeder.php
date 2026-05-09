<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PalmaresFromStatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('stats') || ! DB::getSchemaBuilder()->hasTable('palmares')) {
            return;
        }

        $rows = DB::query()
            ->fromSub(
                DB::query()
                    ->fromSub(
                        DB::table('stats')
                            ->selectRaw(
                                'sport_id, team_id, YEAR(created_at) as season_year, SUM(point_count) as total_points'
                            )
                            ->groupByRaw('sport_id, team_id, YEAR(created_at)'),
                        'season_points'
                    )
                    ->selectRaw(
                        'sport_id, team_id, season_year, total_points, '.
                        'ROW_NUMBER() OVER (PARTITION BY sport_id, season_year ORDER BY total_points DESC, team_id ASC) as rank_position'
                    ),
                'ranked_points'
            )
            ->where('rank_position', '<=', 3)
            ->orderBy('sport_id')
            ->orderBy('season_year')
            ->orderBy('rank_position')
            ->get([
                'sport_id',
                'team_id',
                'season_year',
                'rank_position',
            ]);

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();
        $payloadBySeasonKey = [];
        foreach ($rows as $row) {
            $seasonYear = (int) $row->season_year;
            $seasonYears = [[
                'start_date' => sprintf('%d-01-01', $seasonYear),
                'end_date' => sprintf('%d-12-31', $seasonYear),
            ]];
            $seasonYearsJson = (string) json_encode($seasonYears, JSON_THROW_ON_ERROR);
            $rank = (int) $row->rank_position;
            $trophy = match ($rank) {
                1 => 'gold',
                2 => 'silver',
                default => 'bronze',
            };

            $sportId = (int) $row->sport_id;
            $seasonKey = $sportId.'|'.$seasonYearsJson;
            if (! isset($payloadBySeasonKey[$seasonKey])) {
                $payloadBySeasonKey[$seasonKey] = [
                    'sport_id' => $sportId,
                    'season_years' => $seasonYearsJson,
                    'rows' => [],
                ];
            }

            $payloadBySeasonKey[$seasonKey]['rows'][] = [
                'sport_id' => $sportId,
                'team_id' => (int) $row->team_id,
                'rank' => $rank,
                'trophy' => $trophy,
                'season_years' => $seasonYearsJson,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($payloadBySeasonKey as $seasonPayload) {
            DB::transaction(function () use ($seasonPayload): void {
                DB::table('palmares')
                    ->where('sport_id', (int) $seasonPayload['sport_id'])
                    ->where('season_years', (string) $seasonPayload['season_years'])
                    ->delete();

                DB::table('palmares')->insert($seasonPayload['rows']);
            });
        }
    }
}
