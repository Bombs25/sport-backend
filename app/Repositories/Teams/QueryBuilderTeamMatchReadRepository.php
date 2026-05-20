<?php

namespace App\Repositories\Teams;

use App\Contracts\Teams\TeamMatchReadRepository;
use App\Support\PublicImageUrl;
use Illuminate\Support\Facades\DB;

class QueryBuilderTeamMatchReadRepository implements TeamMatchReadRepository
{
    public function findLatestValidatedMatchForTeam(int $teamId): ?array
    {
        $row = DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->where('match_results.status', 'validated')
            ->where('match_events.status', 'finished')
            ->where(static function ($query) use ($teamId): void {
                $query->where('match_events.home_team_id', $teamId)
                    ->orWhere('match_events.away_team_id', $teamId);
            })
            ->orderByDesc('match_results.validated_at')
            ->orderByDesc('match_results.id')
            ->limit(1)
            ->select([
                'match_events.id as match_event_id',
                'match_results.id as match_result_id',
                'match_events.home_team_id',
                'match_events.away_team_id',
                'home_teams.name as home_team_name',
                'away_teams.name as away_team_name',
                'home_teams.logo_url as home_team_logo_url',
                'away_teams.logo_url as away_team_logo_url',
                'match_results.home_score',
                'match_results.away_score',
                'match_results.validated_at',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'match_event_id' => (int) $row->match_event_id,
            'match_result_id' => (int) $row->match_result_id,
            'home_team_id' => (int) $row->home_team_id,
            'away_team_id' => (int) $row->away_team_id,
            'home_team_name' => (string) $row->home_team_name,
            'away_team_name' => (string) $row->away_team_name,
            'home_team_logo_url' => PublicImageUrl::from($row->home_team_logo_url),
            'away_team_logo_url' => PublicImageUrl::from($row->away_team_logo_url),
            'home_score' => (int) $row->home_score,
            'away_score' => (int) $row->away_score,
            'validated_at' => $row->validated_at !== null ? (string) $row->validated_at : null,
        ];
    }

    public function listValidatedMatchesForTeam(int $teamId, int $limit = 50): array
    {
        $safeLimit = max(1, min($limit, 50));

        return DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->where('match_results.status', 'validated')
            ->where('match_events.status', 'finished')
            ->where(static function ($query) use ($teamId): void {
                $query->where('match_events.home_team_id', $teamId)
                    ->orWhere('match_events.away_team_id', $teamId);
            })
            ->orderByDesc('match_results.validated_at')
            ->orderByDesc('match_results.id')
            ->limit($safeLimit)
            ->select([
                'match_events.id as match_event_id',
                'match_results.id as match_result_id',
                'match_events.home_team_id',
                'match_events.away_team_id',
                'home_teams.name as home_team_name',
                'away_teams.name as away_team_name',
                'match_results.home_score',
                'match_results.away_score',
                'match_results.validated_at',
                'match_events.scheduled_at',
                'match_events.venue',
            ])
            ->get()
            ->map(static fn (object $row): array => [
                'match_event_id' => (int) $row->match_event_id,
                'match_result_id' => (int) $row->match_result_id,
                'home_team_id' => (int) $row->home_team_id,
                'away_team_id' => (int) $row->away_team_id,
                'home_team_name' => (string) $row->home_team_name,
                'away_team_name' => (string) $row->away_team_name,
                'home_score' => (int) $row->home_score,
                'away_score' => (int) $row->away_score,
                'validated_at' => $row->validated_at !== null ? (string) $row->validated_at : null,
                'scheduled_at' => $row->scheduled_at !== null ? (string) $row->scheduled_at : null,
                'venue' => $row->venue !== null ? (string) $row->venue : null,
            ])
            ->all();
    }
}
