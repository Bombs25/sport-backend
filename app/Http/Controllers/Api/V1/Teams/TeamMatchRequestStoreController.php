<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchRequestStoreRequest;
use App\Jobs\TeamMatchRequestNotificationJob;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMatchRequestStoreController extends Controller
{
    public function __invoke(TeamMatchRequestStoreRequest $request, TeamService $service, int $team_id): JsonResponse
    {
        $validatedTeamId = filter_var($team_id, FILTER_VALIDATE_INT);
        if ($validatedTeamId === false) {
            abort(400, __('Identifiant d\'équipe invalide.'));
        }
        $homeTeam = Team::query()->findOrFail($validatedTeamId); // LE HOME TEAM EST L'EQUIPE QUI FAIT LA REQUETE

        $actorUserId = (int) $request->user()->id;

        $matchEventId = $service->requestMatch(
            $homeTeam,
            $actorUserId,
            (int) $request->validated()['away_team_id'],
            $request->validated()['scheduled_at'],
            $request->validated()['venue'] ?? null,
            $request->validated()['notes'] ?? null,
        );

        TeamMatchRequestNotificationJob::dispatch($matchEventId, $actorUserId)
            ->onQueue('post_notifications');

        return response()->json([
            'message' => __('Demande de match envoyée.'),
            'match_event_id' => $matchEventId,
        ], 201);
    }
}
