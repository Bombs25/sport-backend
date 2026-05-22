<?php

namespace App\Repositories\Teams;

use App\Contracts\Teams\TeamMatchReadRepository;
use App\Support\PublicImageUrl;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class QueryBuilderTeamMatchReadRepository implements TeamMatchReadRepository
{
    /** Nombre de matchs renvoyés par page d'historique. */
    private const HISTORY_PAGE_SIZE = 10;

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

    public function listValidatedMatchesForTeam(int $teamId, int $page = 1): array
    {
        $safePage = max(1, $page);
        $perPage = self::HISTORY_PAGE_SIZE;

        $total = $this->validatedMatchesBaseQuery($teamId)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $matches = $this->validatedMatchesBaseQuery($teamId)
            ->orderByDesc('match_results.validated_at')
            ->orderByDesc('match_results.id')
            ->forPage($safePage, $perPage)
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
                'match_results.total_likes',
                'match_results.total_comments',
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
                'home_team_logo_url' => PublicImageUrl::from($row->home_team_logo_url),
                'away_team_logo_url' => PublicImageUrl::from($row->away_team_logo_url),
                'home_score' => (int) $row->home_score,
                'away_score' => (int) $row->away_score,
                'total_likes' => (int) $row->total_likes,
                'total_comments' => (int) $row->total_comments,
                'validated_at' => $row->validated_at !== null ? (string) $row->validated_at : null,
                'scheduled_at' => $row->scheduled_at !== null ? (string) $row->scheduled_at : null,
                'venue' => $row->venue !== null ? (string) $row->venue : null,
            ])
            ->all();

        return [
            'matches' => $matches,
            'meta' => [
                'current_page' => $safePage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more' => $safePage < $lastPage,
            ],
        ];
    }

    /**
     * Base commune (jointures + filtres) des matchs validés terminés d'une équipe,
     * partagée entre le `count()` de pagination et la requête de liste.
     */
    private function validatedMatchesBaseQuery(int $teamId): Builder
    {
        return DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->where('match_results.status', 'validated')
            ->where('match_events.status', 'finished')
            ->where(static function ($query) use ($teamId): void {
                $query->where('match_events.home_team_id', $teamId)
                    ->orWhere('match_events.away_team_id', $teamId);
            });
    }
}
