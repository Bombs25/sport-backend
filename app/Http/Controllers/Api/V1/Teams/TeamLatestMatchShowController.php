<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Contracts\Teams\TeamMatchReadRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamLatestMatchRequest;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : renvoie le dernier match à score validé pour l'écran « Latest Match ».
 *
 * Pourquoi : jointure match_results + match_events + teams en Query Builder (TeamMatchReadRepository).
 */
class TeamLatestMatchShowController extends Controller
{
    public function __invoke(TeamLatestMatchRequest $request, TeamMatchReadRepository $matches): JsonResponse
    {
        $teamId = (int) $request->validated('team_id');
        $raw = $matches->findLatestValidatedMatchForTeam($teamId);

        if ($raw === null) {
            return response()->json([
                'data' => [
                    'latest_match' => null,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'latest_match' => [
                    'match_event_id' => $raw['match_event_id'],
                    'match_result_id' => $raw['match_result_id'],
                    'validated_at' => $raw['validated_at'],
                    'home' => [
                        'team_id' => $raw['home_team_id'],
                        'name' => $raw['home_team_name'],
                        'logo_url' => $raw['home_team_logo_url'],
                        'score' => $raw['home_score'],
                    ],
                    'away' => [
                        'team_id' => $raw['away_team_id'],
                        'name' => $raw['away_team_name'],
                        'logo_url' => $raw['away_team_logo_url'],
                        'score' => $raw['away_score'],
                    ],
                    'outcome_for_viewing_team' => $this->outcomeForViewingTeam(
                        $teamId,
                        $raw['home_team_id'],
                        $raw['home_score'],
                        $raw['away_score'],
                    ),
                ],
            ],
        ]);
    }

    private function outcomeForViewingTeam(
        int $viewingTeamId,
        int $homeTeamId,
        int $homeScore,
        int $awayScore,
    ): string {
        if ($homeScore === $awayScore) {
            return 'draw';
        }

        $viewingIsHome = $viewingTeamId === $homeTeamId;
        $homeWins = $homeScore > $awayScore;

        if ($viewingIsHome) {
            return $homeWins ? 'win' : 'loss';
        }

        return $homeWins ? 'loss' : 'win';
    }
}
