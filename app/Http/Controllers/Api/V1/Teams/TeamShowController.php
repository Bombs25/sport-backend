<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : détail d’une équipe pour un **membre actif** (sport joint, effectif).
 */
class TeamShowController extends Controller
{
    public function __invoke(Team $team, TeamService $service): JsonResponse
    {
        $this->authorize('view', $team);

        return response()->json([
            'data' => $service->buildDetailPayload($team),
        ]);
    }
}
