<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchRequestStoreRequest;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMatchRequestStoreController extends Controller
{
    public function __invoke(TeamMatchRequestStoreRequest $request, TeamService $service, int $team_id): JsonResponse
    {
        $homeTeam = Team::query()->findOrFail($team_id);

        $matchEventId = $service->requestMatch(
            $homeTeam,
            (int) $request->user()->id,
            (int) $request->validated()['away_team_id'],
            $request->validated()['scheduled_at'],
            $request->validated()['venue'] ?? null,
            $request->validated()['notes'] ?? null,
        );

        return response()->json([
            'message' => __('Demande de match envoyée.'),
            'match_event_id' => $matchEventId,
        ], 201);
    }
}
