<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : supprime définitivement une équipe (**créateur** uniquement) et les membres en cascade.
 */
class TeamDestroyController extends Controller
{
    public function __invoke(int $team_id, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail($team_id);
        $this->authorize('delete', $team);

        $service->deleteTeam($team);

        return response()->json([
            'message' => __('Équipe supprimée.'),
        ]);
    }
}
