<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamStoreRequest;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : crée une équipe et rattache le créateur comme **captain** actif dans `team_members`.
 */
class TeamStoreController extends Controller
{
    public function __invoke(TeamStoreRequest $request, TeamService $service): JsonResponse
    {
        $team = $service->createForCreator((int) $request->user()->id, $request->validated());

        return response()->json([
            'message' => __('Équipe créée.'),
            'team' => $service->buildDetailPayload($team),
        ], 201);
    }
}
