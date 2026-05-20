<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Contracts\Teams\TeamMatchReadRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamLatestMatchRequest;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : liste les matchs validés d'une équipe pour l'historique profil.
 */
class TeamMatchHistoryListController extends Controller
{
    public function __invoke(TeamLatestMatchRequest $request, TeamMatchReadRepository $matches): JsonResponse
    {
        $teamId = (int) $request->validated('team_id');
        $rawMatches = $matches->listValidatedMatchesForTeam($teamId);

        $items = array_map(
            static fn (array $raw): array => [
                'match_event_id' => $raw['match_event_id'],
                'match_result_id' => $raw['match_result_id'],
                'validated_at' => $raw['validated_at'],
                'scheduled_at' => $raw['scheduled_at'],
                'venue' => $raw['venue'],
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
            ],
            $rawMatches,
        );

        return response()->json([
            'data' => [
                'matches' => $items,
            ],
        ]);
    }
}
