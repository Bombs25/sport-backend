<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamUpdateRequest;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : met à jour une équipe (créateur ou **captain** actif) ; régénère le **slug** si le nom change.
 */
class TeamUpdateController extends Controller
{
    public function __invoke(TeamUpdateRequest $request, Team $team, TeamService $service): JsonResponse
    {
        $service->updateTeam($team, $request->validated());

        return response()->json([
            'message' => __('Équipe mise à jour.'),
            'team' => $service->buildDetailPayload($team),
        ]);
    }
}
