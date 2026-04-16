<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchResultStoreRequest;
use App\Services\Team\MatchResultService;
use Illuminate\Http\JsonResponse;

class TeamMatchResultStoreController extends Controller
{
    /**
     * `team_id` doit être le **home_team_id** du match (équipe demanderesse, même que pour POST match-requests).
     */
    public function __invoke(
        TeamMatchResultStoreRequest $request,
        MatchResultService $service,
        int $team_id,
        int $match_event_id,
    ): JsonResponse {
        $validated = $request->validated();
        $payload = $service->submitScoreAndFirstEvaluation(
            $match_event_id,
            $team_id,
            (int) $request->user()->id,
            (int) $validated['home_score'],
            (int) $validated['away_score'],
            (int) $validated['fair_play_rating'],
            (int) $validated['punctuality_rating'],
            $validated['remarks'] ?? null,
        );

        return response()->json([
            'message' => $payload['created']
                ? __('Score et évaluation enregistrés.')
                : __('Score et évaluation mis à jour.'),
            'match_result_id' => $payload['match_result_id'],
        ], $payload['created'] ? 201 : 200);
    }
}
